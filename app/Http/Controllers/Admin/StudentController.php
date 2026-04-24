<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ParentProfile;
use App\Models\ParentStudent;
use App\Models\SchoolClass;
use App\Models\SchoolLevel;
use App\Models\StudentProfile;
use App\Models\User;
use App\Notifications\PasswordResetByAdmin;
use App\Notifications\WelcomeNewUser;
use App\Services\FileUploadService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

class StudentController extends Controller
{
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

        return view('admin.students.show', compact('student'));
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
}
