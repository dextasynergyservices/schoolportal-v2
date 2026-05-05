<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\ParentProfile;
use App\Models\ParentStudent;
use App\Models\SchoolClass;
use App\Models\SchoolLevel;
use App\Models\StudentProfile;
use App\Models\StudentSubjectScore;
use App\Models\StudentTermReport;
use App\Models\User;
use App\Notifications\PasswordResetByAdmin;
use App\Notifications\StudentMovedClassNotification;
use App\Notifications\WelcomeNewUser;
use App\Services\FileUploadService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class StudentController extends Controller
{
    /**
     * Show the bulk move student UI.
     */
    public function moveForm(Request $request): View
    {
        $schoolId = app('current.school')->id;
        $levels = SchoolLevel::where('school_id', $schoolId)->orderBy('sort_order')->get();
        $selectedLevel = $request->input('level_id');
        $selectedClass = $request->input('class_id');
        $classes = $selectedLevel
            ? SchoolClass::where('school_id', $schoolId)->where('level_id', $selectedLevel)->orderBy('sort_order')->get()
            : collect();
        $students = $selectedClass
            ? User::where('school_id', $schoolId)->where('role', 'student')->where('level_id', $selectedLevel)->whereHas('studentProfile', function ($q) use ($selectedClass) {
                $q->where('class_id', $selectedClass);
            })->orderBy('name')->get()
            : collect();

        return view('admin.students.move', compact('levels', 'classes', 'students', 'selectedLevel', 'selectedClass'));
    }

    /**
     * Process bulk move of students to another class within a level.
     */
    public function moveProcess(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'level_id' => ['required', 'exists:school_levels,id'],
            'class_id' => ['required', 'exists:classes,id'],
            'target_class_id' => ['required', 'exists:classes,id', 'different:class_id'],
            'student_ids' => ['required', 'array', 'min:1'],
            'student_ids.*' => ['exists:users,id'],
        ]);

        $levelId = (int) $validated['level_id'];
        $fromClassId = (int) $validated['class_id'];
        $toClassId = (int) $validated['target_class_id'];
        $studentIds = $validated['student_ids'];

        // Ensure both classes belong to the selected level and current school.
        $schoolId = app('current.school')->id;
        $fromClass = SchoolClass::where('school_id', $schoolId)
            ->where('id', $fromClassId)
            ->where('level_id', $levelId)
            ->firstOrFail();

        $toClass = SchoolClass::where('school_id', $schoolId)
            ->where('id', $toClassId)
            ->where('level_id', $levelId)
            ->firstOrFail();

        $students = User::with(['studentProfile', 'parentUsers'])
            ->where('school_id', $schoolId)
            ->whereIn('id', $studentIds)
            ->where('role', 'student')
            ->whereHas('studentProfile', function ($q) use ($fromClassId) {
                $q->where('class_id', $fromClassId);
            })
            ->get();

        $moved = 0;

        DB::transaction(function () use ($students, $toClassId, $fromClassId, $studentIds, $request, &$moved) {
            foreach ($students as $student) {
                $student->studentProfile->update([
                    'class_id' => $toClassId,
                ]);

                $moved++;
            }

            // Optional audit log.
            if (class_exists(AuditLog::class)) {
                AuditLog::create([
                    'school_id' => auth()->user()->school_id,
                    'user_id' => auth()->id(),
                    'action' => 'students.moved_class',
                    'entity_type' => 'class',
                    'entity_id' => $toClassId,
                    'old_values' => json_encode(['from_class_id' => $fromClassId, 'student_ids' => $studentIds]),
                    'new_values' => json_encode(['to_class_id' => $toClassId, 'student_ids' => $studentIds]),
                    'ip_address' => $request->ip(),
                    'user_agent' => $request->userAgent(),
                ]);
            }
        });

        // Send notifications after the database transaction succeeds.
        foreach ($students as $student) {
            try {
                $student->notify(new StudentMovedClassNotification(
                    fromClassName: $fromClass->name,
                    toClassName: $toClass->name,
                    studentName: $student->name,
                    recipientRole: 'student',
                ));
            } catch (\Throwable $e) {
                report($e);
            }

            foreach ($student->parentUsers as $parentUser) {
                try {
                    $parentUser->notify(new StudentMovedClassNotification(
                        fromClassName: $fromClass->name,
                        toClassName: $toClass->name,
                        studentName: $student->name,
                        recipientRole: 'parent',
                    ));
                } catch (\Throwable $e) {
                    report($e);
                }
            }
        }

        return redirect()->route('admin.students.move', [
            'level_id' => $levelId,
            'class_id' => $fromClassId,
        ])->with('success', "{$moved} student(s) moved to {$toClass->name} successfully.");
    }

    public function index(Request $request): View
    {
        $query = User::where('role', 'student')
            ->with(['studentProfile.class:id,name', 'level:id,name']);

        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('username', 'like', "%{$search}%");
            });
        }

        if ($request->filled('class_id')) {
            $query->whereHas('studentProfile', fn ($q) => $q->where('class_id', $request->input('class_id')));
        }

        if ($request->filled('level_id')) {
            $query->where('level_id', $request->input('level_id'));
        }

        if ($request->filled('status')) {
            $query->where('is_active', $request->input('status') === 'active');
        }

        $students = $query->orderBy('name')->paginate(10)->withQueryString();
        $classes = SchoolClass::where('is_active', true)->orderBy('name')->get();
        $levels = SchoolLevel::where('is_active', true)->orderBy('sort_order')->get();

        return view('admin.students.index', compact('students', 'classes', 'levels'));
    }

    public function exportCsv(Request $request): StreamedResponse
    {
        $query = User::where('role', 'student')
            ->with(['studentProfile.class:id,name', 'level:id,name']);

        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('username', 'like', "%{$search}%");
            });
        }

        if ($request->filled('class_id')) {
            $query->whereHas('studentProfile', fn ($q) => $q->where('class_id', $request->input('class_id')));
        }

        if ($request->filled('level_id')) {
            $query->where('level_id', $request->input('level_id'));
        }

        if ($request->filled('status')) {
            $query->where('is_active', $request->input('status') === 'active');
        }

        $students = $query->orderBy('name')->get();
        $filename = 'students-'.now()->format('Y-m-d').'.csv';

        return response()->streamDownload(function () use ($students) {
            $handle = fopen('php://output', 'w');
            fwrite($handle, "\xEF\xBB\xBF");
            fputcsv($handle, ['Name', 'Username', 'Gender', 'Level', 'Class', 'Admission No.', 'Date of Birth', 'Status', 'Created At']);
            foreach ($students as $student) {
                fputcsv($handle, [
                    $student->name,
                    $student->username,
                    $student->gender ?? '',
                    $student->level?->name ?? '',
                    $student->studentProfile?->class?->name ?? '',
                    $student->studentProfile?->admission_number ?? '',
                    $student->studentProfile?->date_of_birth?->format('Y-m-d') ?? '',
                    $student->is_active ? 'Active' : 'Inactive',
                    $student->created_at->format('Y-m-d H:i'),
                ]);
            }
            fclose($handle);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    public function create(): View
    {
        $classes = SchoolClass::with('level:id,name')
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        $levels = SchoolLevel::where('is_active', true)->orderBy('sort_order')->get();
        $currentSession = app('current.school')->currentSession();
        $parents = User::where('role', 'parent')
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'username', 'phone']);

        return view('admin.students.create', compact('classes', 'levels', 'currentSession', 'parents'));
    }

    public function store(Request $request, FileUploadService $uploadService): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'username' => ['required', 'string', 'max:100', 'unique:users,username'],
            'password' => ['required', 'string', Password::defaults()],
            'gender' => ['required', 'in:male,female'],
            'level_id' => ['required', 'exists:school_levels,id'],
            'class_id' => ['required', 'exists:classes,id'],
            'avatar' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'admission_number' => ['nullable', 'string', 'max:50'],
            'date_of_birth' => ['nullable', 'date'],
            'address' => ['nullable', 'string'],
            'blood_group' => ['nullable', 'string', 'max:5'],
            'medical_notes' => ['nullable', 'string'],
            'parent_ids' => ['nullable', 'array'],
            'parent_ids.*' => ['exists:users,id'],
            'new_parent_name' => ['nullable', 'string', 'max:255'],
            'new_parent_username' => ['nullable', 'string', 'max:100', 'unique:users,username'],
            'new_parent_password' => ['nullable', 'string', 'min:6'],
            'new_parent_phone' => ['nullable', 'string', 'max:20'],
            'new_parent_relationship' => ['nullable', 'in:father,mother,guardian,other'],
        ]);

        // Upload avatar to Cloudinary if provided
        $avatarUrl = null;
        if ($request->hasFile('avatar')) {
            $schoolId = app('current.school')->id;
            $uploaded = $uploadService->uploadAvatar($request->file('avatar'), $schoolId);
            $avatarUrl = $uploaded['url'];
        }

        $user = DB::transaction(function () use ($validated, $avatarUrl) {
            $user = User::create([
                'name' => $validated['name'],
                'username' => $validated['username'],
                'password' => Hash::make($validated['password']),
                'role' => 'student',
                'gender' => $validated['gender'],
                'level_id' => $validated['level_id'],
                'avatar_url' => $avatarUrl,
                'must_change_password' => true,
            ]);

            StudentProfile::create([
                'user_id' => $user->id,
                'school_id' => $user->school_id,
                'class_id' => $validated['class_id'],
                'admission_number' => $validated['admission_number'] ?? null,
                'date_of_birth' => $validated['date_of_birth'] ?? null,
                'address' => $validated['address'] ?? null,
                'blood_group' => $validated['blood_group'] ?? null,
                'medical_notes' => $validated['medical_notes'] ?? null,
                'enrolled_session_id' => app('current.school')->currentSession()?->id,
            ]);

            // Link existing parents
            if (! empty($validated['parent_ids'])) {
                foreach ($validated['parent_ids'] as $parentId) {
                    ParentStudent::firstOrCreate([
                        'parent_id' => $parentId,
                        'student_id' => $user->id,
                    ], [
                        'school_id' => $user->school_id,
                    ]);
                }
            }

            // Create new parent inline if provided
            if (! empty($validated['new_parent_name']) && ! empty($validated['new_parent_username']) && ! empty($validated['new_parent_password'])) {
                $newParent = User::create([
                    'name' => $validated['new_parent_name'],
                    'username' => $validated['new_parent_username'],
                    'password' => Hash::make($validated['new_parent_password']),
                    'role' => 'parent',
                    'phone' => $validated['new_parent_phone'] ?? null,
                    'must_change_password' => true,
                ]);

                ParentProfile::create([
                    'user_id' => $newParent->id,
                    'school_id' => $newParent->school_id,
                    'relationship' => $validated['new_parent_relationship'] ?? null,
                ]);

                ParentStudent::create([
                    'parent_id' => $newParent->id,
                    'student_id' => $user->id,
                    'school_id' => $user->school_id,
                ]);
            }

            return $user;
        });

        if ($user->email) {
            $user->notify(new WelcomeNewUser('student', $user->username, app('current.school')->name, $validated['password']));
        }

        return redirect()->route('admin.students.index')
            ->with('success', __('Student ":name" created.', ['name' => $validated['name']]));
    }

    public function show(User $student): View
    {
        abort_unless($student->role === 'student', 404);

        $student->load(['studentProfile.class.level', 'studentProfile.class.teacher', 'level']);

        // Sibling classes: same level, active, excluding current class
        $siblingClasses = collect();
        if ($student->studentProfile?->class?->level_id) {
            $siblingClasses = SchoolClass::where('level_id', $student->studentProfile->class->level_id)
                ->where('is_active', true)
                ->where('id', '!=', $student->studentProfile->class_id)
                ->orderBy('sort_order')
                ->orderBy('name')
                ->get(['id', 'name']);
        }

        return view('admin.students.show', compact('student', 'siblingClasses'));
    }

    public function edit(User $student): View
    {
        abort_unless($student->role === 'student', 404);

        $student->load('studentProfile');
        $classes = SchoolClass::with('level:id,name')
            ->where('is_active', true)
            ->orderBy('name')
            ->get();
        $levels = SchoolLevel::where('is_active', true)->orderBy('sort_order')->get();
        $parents = User::where('role', 'parent')
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'username', 'phone']);
        $linkedParentIds = ParentStudent::where('student_id', $student->id)->pluck('parent_id')->toArray();

        return view('admin.students.edit', compact('student', 'classes', 'levels', 'parents', 'linkedParentIds'));
    }

    public function update(Request $request, User $student, FileUploadService $uploadService): RedirectResponse
    {
        abort_unless($student->role === 'student', 404);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'username' => ['required', 'string', 'max:100', "unique:users,username,{$student->id}"],
            'password' => ['nullable', 'string', Password::defaults()],
            'gender' => ['required', 'in:male,female'],
            'level_id' => ['required', 'exists:school_levels,id'],
            'class_id' => ['required', 'exists:classes,id'],
            'is_active' => ['boolean'],
            'avatar' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'remove_avatar' => ['nullable'],
            'admission_number' => ['nullable', 'string', 'max:50'],
            'date_of_birth' => ['nullable', 'date'],
            'address' => ['nullable', 'string'],
            'blood_group' => ['nullable', 'string', 'max:5'],
            'medical_notes' => ['nullable', 'string'],
            'parent_ids' => ['nullable', 'array'],
            'parent_ids.*' => ['exists:users,id'],
            'new_parent_name' => ['nullable', 'string', 'max:255'],
            'new_parent_username' => ['nullable', 'string', 'max:100', 'unique:users,username'],
            'new_parent_password' => ['nullable', 'string', 'min:6'],
            'new_parent_phone' => ['nullable', 'string', 'max:20'],
            'new_parent_relationship' => ['nullable', 'in:father,mother,guardian,other'],
        ]);

        // Handle avatar upload/removal
        $avatarUrl = $student->avatar_url;
        if ($request->hasFile('avatar')) {
            $schoolId = app('current.school')->id;
            $uploaded = $uploadService->uploadAvatar($request->file('avatar'), $schoolId);
            $avatarUrl = $uploaded['url'];
        } elseif ($request->boolean('remove_avatar')) {
            $avatarUrl = null;
        }

        DB::transaction(function () use ($validated, $student, $avatarUrl) {
            $userData = [
                'name' => $validated['name'],
                'username' => $validated['username'],
                'gender' => $validated['gender'],
                'level_id' => $validated['level_id'],
                'is_active' => $validated['is_active'] ?? true,
                'avatar_url' => $avatarUrl,
            ];

            if (! empty($validated['password'])) {
                $userData['password'] = Hash::make($validated['password']);
                $userData['must_change_password'] = true;
            }

            $student->update($userData);

            $student->studentProfile()->updateOrCreate(
                ['user_id' => $student->id],
                [
                    'school_id' => $student->school_id,
                    'class_id' => $validated['class_id'],
                    'admission_number' => $validated['admission_number'] ?? null,
                    'date_of_birth' => $validated['date_of_birth'] ?? null,
                    'address' => $validated['address'] ?? null,
                    'blood_group' => $validated['blood_group'] ?? null,
                    'medical_notes' => $validated['medical_notes'] ?? null,
                ],
            );

            // Sync parent links
            ParentStudent::where('student_id', $student->id)->delete();
            if (! empty($validated['parent_ids'])) {
                foreach ($validated['parent_ids'] as $parentId) {
                    ParentStudent::create([
                        'parent_id' => $parentId,
                        'student_id' => $student->id,
                        'school_id' => $student->school_id,
                    ]);
                }
            }

            // Create new parent inline if provided
            if (! empty($validated['new_parent_name']) && ! empty($validated['new_parent_username']) && ! empty($validated['new_parent_password'])) {
                $newParent = User::create([
                    'name' => $validated['new_parent_name'],
                    'username' => $validated['new_parent_username'],
                    'password' => Hash::make($validated['new_parent_password']),
                    'role' => 'parent',
                    'phone' => $validated['new_parent_phone'] ?? null,
                    'must_change_password' => true,
                ]);

                ParentProfile::create([
                    'user_id' => $newParent->id,
                    'school_id' => $newParent->school_id,
                    'relationship' => $validated['new_parent_relationship'] ?? null,
                ]);

                ParentStudent::create([
                    'parent_id' => $newParent->id,
                    'student_id' => $student->id,
                    'school_id' => $student->school_id,
                ]);
            }
        });

        return redirect()->route('admin.students.index')
            ->with('success', __('Student updated.'));
    }

    public function resetPassword(Request $request, User $student): RedirectResponse
    {
        abort_unless($student->role === 'student', 404);

        $validated = $request->validate([
            'password' => ['required', 'string', Password::defaults()],
        ]);

        $student->update([
            'password' => Hash::make($validated['password']),
            'must_change_password' => true,
        ]);

        if ($student->email) {
            $student->notify(new PasswordResetByAdmin(app('current.school')->name));
        }

        return back()->with('success', __('Password reset for :name. They will be prompted to change it on next login.', ['name' => $student->name]));
    }

    public function destroy(User $student): RedirectResponse
    {
        abort_unless($student->role === 'student', 404);

        $name = $student->name;
        $student->delete();

        return redirect()->route('admin.students.index')
            ->with('success', __('Student ":name" deleted.', ['name' => $name]));
    }

    public function deactivate(Request $request, User $student): RedirectResponse
    {
        abort_unless($student->role === 'student', 404);

        $validated = $request->validate([
            'deactivation_reason' => ['required', 'string', 'max:1000'],
        ]);

        $student->update([
            'is_active' => false,
            'deactivation_reason' => $validated['deactivation_reason'],
            'deactivated_at' => now(),
        ]);

        return back()->with('success', __('":name" has been deactivated.', ['name' => $student->name]));
    }

    public function activate(User $student): RedirectResponse
    {
        abort_unless($student->role === 'student', 404);

        $student->update([
            'is_active' => true,
            'deactivation_reason' => null,
            'deactivated_at' => null,
        ]);

        return back()->with('success', __('":name" has been activated.', ['name' => $student->name]));
    }

    public function transferClass(Request $request, User $student): RedirectResponse
    {
        abort_unless($student->role === 'student', 404);
        abort_unless($student->studentProfile, 404);

        $validated = $request->validate([
            'class_id' => ['required', 'exists:classes,id'],
        ]);

        $newClass = SchoolClass::findOrFail($validated['class_id']);
        $currentClass = $student->studentProfile->class;

        // Ensure the new class is within the same level
        abort_unless(
            $currentClass && $newClass->level_id === $currentClass->level_id,
            422,
            __('Students can only be transferred to another class within the same level.')
        );

        // Ensure the new class is different from current
        if ($newClass->id === $currentClass->id) {
            return back()->with('error', __('The student is already in this class.'));
        }

        $student->studentProfile->update([
            'class_id' => $newClass->id,
        ]);

        return back()->with('success', __('":name" has been transferred from :from to :to.', [
            'name' => $student->name,
            'from' => $currentClass->name,
            'to' => $newClass->name,
        ]));
    }

    /**
     * Export a single student's full profile as CSV (GDPR data portability).
     */
    public function exportFullProfileCsv(User $student): StreamedResponse
    {
        abort_unless($student->role === 'student', 404);

        $student->load([
            'studentProfile.class.level',
            'level',
            'parentUsers',
        ]);

        $profile = $student->studentProfile;
        $class = $profile?->class;
        $level = $class?->level ?? $student->level;

        $parents = $student->parentUsers->map(function (User $parent) {
            return $parent->name.($parent->phone ? ' ('.$parent->phone.')' : '');
        })->implode(' | ');

        $filename = 'student-profile-'.$student->id.'-'.now()->format('Y-m-d').'.csv';

        return response()->streamDownload(function () use ($student, $profile, $class, $level, $parents) {
            $handle = fopen('php://output', 'w');

            // UTF-8 BOM for Excel compatibility.
            fwrite($handle, "\xEF\xBB\xBF");

            fputcsv($handle, [
                'Field', 'Value',
            ]);

            $rows = [
                ['Name', $student->name],
                ['Username', $student->username],
                ['Email', $student->email ?? ''],
                ['Phone', $student->phone ?? ''],
                ['Gender', $student->gender ?? ''],
                ['Level', $level?->name ?? ''],
                ['Class', $class?->name ?? ''],
                ['Admission Number', $profile?->admission_number ?? ''],
                ['Date of Birth', $profile?->date_of_birth?->format('Y-m-d') ?? ''],
                ['Blood Group', $profile?->blood_group ?? ''],
                ['Address', $profile?->address ?? ''],
                ['Medical Notes', $profile?->medical_notes ?? ''],
                ['Parents / Guardians', $parents],
                ['Status', $student->is_active ? 'Active' : 'Inactive'],
                ['Account Created', $student->created_at->format('Y-m-d H:i')],
                ['Last Login', $student->last_login_at?->format('Y-m-d H:i') ?? 'Never'],
                ['Is Anonymized', $student->is_anonymized ? 'Yes' : 'No'],
            ];

            foreach ($rows as $row) {
                fputcsv($handle, $row);
            }

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    /**
     * Export a student's full academic records (all terms/sessions/subjects) as CSV.
     */
    public function exportAcademicRecordsCsv(User $student): StreamedResponse
    {
        abort_unless($student->role === 'student', 404);

        $scores = StudentSubjectScore::where('student_id', $student->id)
            ->with([
                'subject:id,name',
                'term:id,name,term_number,session_id',
                'term.session:id,name',
                'class:id,name',
            ])
            ->orderBy('session_id')
            ->orderBy('term_id')
            ->orderBy('subject_id')
            ->get();

        // Index term reports by [session_id][term_id] for quick lookup.
        $reports = StudentTermReport::where('student_id', $student->id)
            ->with(['session:id,name', 'term:id,name'])
            ->get()
            ->keyBy(fn ($r) => $r->session_id.'-'.$r->term_id);

        $admissionNo = $student->studentProfile?->admission_number ?? $student->id;
        $filename = 'academic-records-'.$admissionNo.'-'.now()->format('Y-m-d').'.csv';

        return response()->streamDownload(function () use ($scores, $reports) {
            $handle = fopen('php://output', 'w');

            fwrite($handle, "\xEF\xBB\xBF");

            fputcsv($handle, [
                'Session',
                'Term',
                'Class',
                'Subject',
                'Score',
                'Max Score',
                'Term Total Score',
                'Term Average Score',
                'Class Position',
                'Out Of',
                'Term Status',
                'Teacher Comment',
            ]);

            foreach ($scores as $score) {
                $reportKey = $score->session_id.'-'.$score->term_id;
                $report = $reports[$reportKey] ?? null;

                fputcsv($handle, [
                    $score->term?->session?->name ?? '',
                    $score->term?->name ?? '',
                    $score->class?->name ?? '',
                    $score->subject?->name ?? '',
                    $score->score,
                    $score->max_score,
                    $report?->total_weighted_score ?? '',
                    $report?->average_weighted_score ?? '',
                    $report?->position ?? '',
                    $report?->out_of ?? '',
                    $report?->status ?? '',
                    $report?->teacher_comment ?? '',
                ]);
            }

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    /**
     * Anonymize a student's personal data (GDPR right to erasure).
     */
    public function anonymize(Request $request, User $student, FileUploadService $uploadService): RedirectResponse
    {
        abort_unless($student->role === 'student', 404);

        if ($student->is_anonymized) {
            return back()->with('error', __('This student record has already been anonymized.'));
        }

        $oldAvatarPublicId = null;

        DB::transaction(function () use ($student, &$oldAvatarPublicId) {
            // If the student has an avatar, capture its public ID for deletion after transaction.
            if ($student->avatar_url) {
                // Extract public_id from Cloudinary URL or use a stored field if available.
                // We store the URL; deletion will be attempted by the service on best-effort.
                $oldAvatarPublicId = $student->avatar_public_id ?? null;
            }

            // Anonymize the user record.
            $student->update([
                'name' => 'Anonymized Student',
                'username' => 'deleted_'.$student->id,
                'email' => null,
                'phone' => null,
                'avatar_url' => null,
                'password' => Hash::make(Str::random(64)),
                'is_anonymized' => true,
                'is_active' => false,
                'deactivation_reason' => 'Data anonymized per GDPR/privacy request.',
                'deactivated_at' => now(),
            ]);

            // Anonymize the student profile.
            if ($student->studentProfile) {
                $student->studentProfile->update([
                    'date_of_birth' => null,
                    'address' => null,
                    'blood_group' => null,
                    'medical_notes' => null,
                    'admission_number' => 'DELETED-'.$student->id,
                ]);
            }

            AuditLog::create([
                'school_id' => $student->school_id,
                'user_id' => auth()->id(),
                'action' => 'student.anonymized',
                'entity_type' => 'user',
                'entity_id' => $student->id,
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ]);
        });

        // Attempt to delete Cloudinary avatar outside the transaction (best-effort).
        if ($oldAvatarPublicId) {
            try {
                $uploadService->delete($oldAvatarPublicId);
            } catch (\Throwable) {
                // Non-fatal: log and continue.
                Log::warning('Failed to delete avatar from Cloudinary during anonymization', [
                    'student_id' => $student->id,
                    'public_id' => $oldAvatarPublicId,
                ]);
            }
        }

        return redirect()->route('admin.students.index')
            ->with('success', __('Student record has been anonymized and all personal data removed.'));
    }
}
