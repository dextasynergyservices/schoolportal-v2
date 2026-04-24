<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SchoolClass;
use App\Models\SchoolLevel;
use App\Models\User;
use App\Notifications\PasswordResetByAdmin;
use App\Notifications\WelcomeNewUser;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

class TeacherController extends Controller
{
    public function index(Request $request): View
    {
        $query = User::where('role', 'teacher')
            ->with(['assignedClasses:id,name,teacher_id', 'level:id,name']);

        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('username', 'like', "%{$search}%");
            });
        }

        $teachers = $query->orderBy('name')->paginate(10)->withQueryString();

        return view('admin.teachers.index', compact('teachers'));
    }

    public function create(): View
    {
        $levels = SchoolLevel::where('is_active', true)->orderBy('sort_order')->get();
        $classes = SchoolClass::with('level:id,name')
            ->where('is_active', true)
            ->orderBy('level_id')
            ->orderBy('sort_order')
            ->get();

        return view('admin.teachers.create', compact('levels', 'classes'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'username' => ['required', 'string', 'max:100', 'unique:users,username'],
            'email' => ['nullable', 'email', 'max:255'],
            'password' => ['required', 'string', Password::defaults()],
            'phone' => ['nullable', 'string', 'max:20'],
            'gender' => ['nullable', 'in:male,female,other'],
            'level_id' => ['nullable', 'exists:school_levels,id'],
            'class_ids' => ['nullable', 'array'],
            'class_ids.*' => ['exists:classes,id'],
        ]);

        $teacher = User::create([
            'name' => $validated['name'],
            'username' => $validated['username'],
            'email' => $validated['email'] ?? null,
            'password' => Hash::make($validated['password']),
            'role' => 'teacher',
            'phone' => $validated['phone'] ?? null,
            'gender' => $validated['gender'] ?? null,
            'level_id' => $validated['level_id'] ?? null,
            'must_change_password' => true,
        ]);

        // Assign classes to teacher
        if (! empty($validated['class_ids'])) {
            SchoolClass::whereIn('id', $validated['class_ids'])->update(['teacher_id' => $teacher->id]);
        }

        if ($teacher->email) {
            $teacher->notify(new WelcomeNewUser('teacher', $teacher->username, app('current.school')->name, $validated['password']));
        }

        return redirect()->route('admin.teachers.index')
            ->with('success', __('Teacher ":name" created.', ['name' => $validated['name']]));
    }

    public function edit(User $teacher): View
    {
        abort_unless($teacher->role === 'teacher', 404);

        $levels = SchoolLevel::where('is_active', true)->orderBy('sort_order')->get();
        $classes = SchoolClass::with('level:id,name')
            ->where('is_active', true)
            ->orderBy('level_id')
            ->orderBy('sort_order')
            ->get();

        $assignedClassIds = $teacher->assignedClasses()->pluck('id')->toArray();

        return view('admin.teachers.edit', compact('teacher', 'levels', 'classes', 'assignedClassIds'));
    }

    public function update(Request $request, User $teacher): RedirectResponse
    {
        abort_unless($teacher->role === 'teacher', 404);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'username' => ['required', 'string', 'max:100', "unique:users,username,{$teacher->id}"],
            'email' => ['nullable', 'email', 'max:255'],
            'password' => ['nullable', 'string', Password::defaults()],
            'phone' => ['nullable', 'string', 'max:20'],
            'gender' => ['nullable', 'in:male,female,other'],
            'level_id' => ['nullable', 'exists:school_levels,id'],
            'is_active' => ['boolean'],
            'class_ids' => ['nullable', 'array'],
            'class_ids.*' => ['exists:classes,id'],
        ]);

        $userData = [
            'name' => $validated['name'],
            'username' => $validated['username'],
            'email' => $validated['email'] ?? null,
            'phone' => $validated['phone'] ?? null,
            'gender' => $validated['gender'] ?? null,
            'level_id' => $validated['level_id'] ?? null,
            'is_active' => $validated['is_active'] ?? true,
        ];

        if (! empty($validated['password'])) {
            $userData['password'] = Hash::make($validated['password']);
            $userData['must_change_password'] = true;
            $userData['must_change_password'] = true;
        }

        $teacher->update($userData);

        // Unassign old classes, assign new ones
        SchoolClass::where('teacher_id', $teacher->id)->update(['teacher_id' => null]);
        if (! empty($validated['class_ids'])) {
            SchoolClass::whereIn('id', $validated['class_ids'])->update(['teacher_id' => $teacher->id]);
        }

        return redirect()->route('admin.teachers.index')
            ->with('success', __('Teacher updated.'));
    }

    public function resetPassword(Request $request, User $teacher): RedirectResponse
    {
        abort_unless($teacher->role === 'teacher', 404);

        $validated = $request->validate([
            'password' => ['required', 'string', Password::defaults()],
        ]);

        $teacher->update([
            'password' => Hash::make($validated['password']),
            'must_change_password' => true,
        ]);

        if ($teacher->email) {
            $teacher->notify(new PasswordResetByAdmin(app('current.school')->name));
        }

        return back()->with('success', __('Password reset for :name. They will be prompted to change it on next login.', ['name' => $teacher->name]));
    }

    public function destroy(User $teacher): RedirectResponse
    {
        abort_unless($teacher->role === 'teacher', 404);

        // Unassign from classes before deleting
        SchoolClass::where('teacher_id', $teacher->id)->update(['teacher_id' => null]);

        $teacher->delete();

        return redirect()->route('admin.teachers.index')
            ->with('success', __('Teacher deleted.'));
    }

    public function deactivate(Request $request, User $teacher): RedirectResponse
    {
        abort_unless($teacher->role === 'teacher', 404);

        $validated = $request->validate([
            'deactivation_reason' => ['required', 'string', 'max:1000'],
        ]);

        $teacher->update([
            'is_active' => false,
            'deactivation_reason' => $validated['deactivation_reason'],
            'deactivated_at' => now(),
        ]);

        return back()->with('success', __('":name" has been deactivated.', ['name' => $teacher->name]));
    }

    public function activate(User $teacher): RedirectResponse
    {
        abort_unless($teacher->role === 'teacher', 404);

        $teacher->update([
            'is_active' => true,
            'deactivation_reason' => null,
            'deactivated_at' => null,
        ]);

        return back()->with('success', __('":name" has been activated.', ['name' => $teacher->name]));
    }
}
