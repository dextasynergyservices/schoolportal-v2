<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ParentProfile;
use App\Models\ParentStudent;
use App\Models\SchoolClass;
use App\Models\SchoolLevel;
use App\Models\User;
use App\Notifications\PasswordResetByAdmin;
use App\Notifications\WelcomeNewUser;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

class ParentController extends Controller
{
    public function index(Request $request): View
    {
        $query = User::where('role', 'parent')
            ->with(['parentProfile']);

        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('username', 'like', "%{$search}%");
            });
        }

        $parents = $query->orderBy('name')->paginate(10)->withQueryString();

        return view('admin.parents.index', compact('parents'));
    }

    public function create(): View
    {
        $levels = SchoolLevel::where('is_active', true)
            ->orderBy('sort_order')
            ->get(['id', 'name']);

        $classes = SchoolClass::where('is_active', true)
            ->orderBy('sort_order')
            ->get(['id', 'name', 'level_id']);

        $students = User::where('role', 'student')
            ->where('is_active', true)
            ->with('studentProfile:id,user_id,class_id,admission_number')
            ->orderBy('name')
            ->get(['id', 'name', 'username']);

        // Build structured data for Alpine.js cascading selection
        $studentData = $students->map(fn (User $s) => [
            'id' => $s->id,
            'name' => $s->name,
            'username' => $s->username,
            'admission_number' => $s->studentProfile?->admission_number,
            'class_id' => $s->studentProfile?->class_id,
        ]);

        return view('admin.parents.create', compact('levels', 'classes', 'studentData'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'username' => ['required', 'string', 'max:100', 'unique:users,username'],
            'password' => ['required', 'string', Password::defaults()],
            'phone' => ['nullable', 'string', 'max:20'],
            'gender' => ['nullable', 'in:male,female'],
            'occupation' => ['nullable', 'string', 'max:255'],
            'relationship' => ['nullable', 'in:father,mother,guardian,other'],
            'address' => ['nullable', 'string'],
            'student_ids' => ['required', 'array', 'min:1'],
            'student_ids.*' => ['exists:users,id'],
        ]);

        $parent = DB::transaction(function () use ($validated) {
            $parent = User::create([
                'name' => $validated['name'],
                'username' => $validated['username'],
                'password' => Hash::make($validated['password']),
                'role' => 'parent',
                'phone' => $validated['phone'] ?? null,
                'gender' => $validated['gender'] ?? null,
                'must_change_password' => true,
            ]);

            ParentProfile::create([
                'user_id' => $parent->id,
                'school_id' => $parent->school_id,
                'occupation' => $validated['occupation'] ?? null,
                'relationship' => $validated['relationship'] ?? null,
                'address' => $validated['address'] ?? null,
            ]);

            // Link to students
            foreach ($validated['student_ids'] as $studentId) {
                ParentStudent::create([
                    'parent_id' => $parent->id,
                    'student_id' => $studentId,
                    'school_id' => $parent->school_id,
                ]);
            }

            return $parent;
        });

        if ($parent->email) {
            $parent->notify(new WelcomeNewUser('parent', $parent->username, app('current.school')->name, $validated['password']));
        }

        return redirect()->route('admin.parents.index')
            ->with('success', __('Parent ":name" created and linked to :count student(s).', [
                'name' => $validated['name'],
                'count' => count($validated['student_ids']),
            ]));
    }

    public function edit(User $parent): View
    {
        abort_unless($parent->role === 'parent', 404);

        $parent->load('parentProfile');

        $levels = SchoolLevel::where('is_active', true)
            ->orderBy('sort_order')
            ->get(['id', 'name']);

        $classes = SchoolClass::where('is_active', true)
            ->orderBy('sort_order')
            ->get(['id', 'name', 'level_id']);

        $students = User::where('role', 'student')
            ->where('is_active', true)
            ->with('studentProfile:id,user_id,class_id,admission_number')
            ->orderBy('name')
            ->get(['id', 'name', 'username']);

        $studentData = $students->map(fn (User $s) => [
            'id' => $s->id,
            'name' => $s->name,
            'username' => $s->username,
            'admission_number' => $s->studentProfile?->admission_number,
            'class_id' => $s->studentProfile?->class_id,
        ]);

        $linkedStudentIds = ParentStudent::where('parent_id', $parent->id)->pluck('student_id')->toArray();

        return view('admin.parents.edit', compact('parent', 'levels', 'classes', 'studentData', 'linkedStudentIds'));
    }

    public function update(Request $request, User $parent): RedirectResponse
    {
        abort_unless($parent->role === 'parent', 404);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'username' => ['required', 'string', 'max:100', "unique:users,username,{$parent->id}"],
            'password' => ['nullable', 'string', Password::defaults()],
            'phone' => ['nullable', 'string', 'max:20'],
            'gender' => ['nullable', 'in:male,female'],
            'is_active' => ['boolean'],
            'occupation' => ['nullable', 'string', 'max:255'],
            'relationship' => ['nullable', 'in:father,mother,guardian,other'],
            'address' => ['nullable', 'string'],
            'student_ids' => ['required', 'array', 'min:1'],
            'student_ids.*' => ['exists:users,id'],
        ]);

        DB::transaction(function () use ($validated, $parent) {
            $userData = [
                'name' => $validated['name'],
                'username' => $validated['username'],
                'phone' => $validated['phone'] ?? null,
                'gender' => $validated['gender'] ?? null,
                'is_active' => $validated['is_active'] ?? true,
            ];

            if (! empty($validated['password'])) {
                $userData['password'] = Hash::make($validated['password']);
                $userData['must_change_password'] = true;
            }

            $parent->update($userData);

            $parent->parentProfile()->updateOrCreate(
                ['user_id' => $parent->id],
                [
                    'school_id' => $parent->school_id,
                    'occupation' => $validated['occupation'] ?? null,
                    'relationship' => $validated['relationship'] ?? null,
                    'address' => $validated['address'] ?? null,
                ],
            );

            // Re-sync student links
            ParentStudent::where('parent_id', $parent->id)->delete();
            foreach ($validated['student_ids'] as $studentId) {
                ParentStudent::create([
                    'parent_id' => $parent->id,
                    'student_id' => $studentId,
                    'school_id' => $parent->school_id,
                ]);
            }
        });

        return redirect()->route('admin.parents.index')
            ->with('success', __('Parent updated.'));
    }

    public function resetPassword(Request $request, User $parent): RedirectResponse
    {
        abort_unless($parent->role === 'parent', 404);

        $validated = $request->validate([
            'password' => ['required', 'string', Password::defaults()],
        ]);

        $parent->update([
            'password' => Hash::make($validated['password']),
            'must_change_password' => true,
        ]);

        if ($parent->email) {
            $parent->notify(new PasswordResetByAdmin(app('current.school')->name));
        }

        return back()->with('success', __('Password reset for :name. They will be prompted to change it on next login.', ['name' => $parent->name]));
    }

    public function destroy(User $parent): RedirectResponse
    {
        abort_unless($parent->role === 'parent', 404);

        $parent->delete();

        return redirect()->route('admin.parents.index')
            ->with('success', __('Parent deleted.'));
    }

    public function deactivate(Request $request, User $parent): RedirectResponse
    {
        abort_unless($parent->role === 'parent', 404);

        $validated = $request->validate([
            'deactivation_reason' => ['required', 'string', 'max:1000'],
        ]);

        $parent->update([
            'is_active' => false,
            'deactivation_reason' => $validated['deactivation_reason'],
            'deactivated_at' => now(),
        ]);

        return back()->with('success', __('":name" has been deactivated.', ['name' => $parent->name]));
    }

    public function activate(User $parent): RedirectResponse
    {
        abort_unless($parent->role === 'parent', 404);

        $parent->update([
            'is_active' => true,
            'deactivation_reason' => null,
            'deactivated_at' => null,
        ]);

        return back()->with('success', __('":name" has been activated.', ['name' => $parent->name]));
    }
}
