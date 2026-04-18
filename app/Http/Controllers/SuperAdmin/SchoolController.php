<?php

declare(strict_types=1);

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreSchoolRequest;
use App\Http\Requests\UpdateSchoolRequest;
use App\Models\School;
use App\Models\User;
use App\Notifications\WelcomeNewUser;
use App\Services\SchoolSetupService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

class SchoolController extends Controller
{
    public function __construct(private readonly SchoolSetupService $setup) {}

    public function index(Request $request): View
    {
        $query = School::tenants()->withCount([
            'users as students_count' => fn ($q) => $q->withoutGlobalScopes()->where('role', 'student'),
            'users as teachers_count' => fn ($q) => $q->withoutGlobalScopes()->where('role', 'teacher'),
        ]);

        if ($search = trim((string) $request->string('search'))) {
            $query->where(function ($q) use ($search): void {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('custom_domain', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        if ($status = $request->string('status')->toString()) {
            $query->where('is_active', $status === 'active');
        }

        $schools = $query->orderByDesc('created_at')->paginate(20)->withQueryString();

        return view('super-admin.schools.index', compact('schools'));
    }

    public function create(): View
    {
        return view('super-admin.schools.create', [
            'levelPresets' => SchoolSetupService::LEVEL_PRESETS,
            'levelNames' => SchoolSetupService::LEVEL_NAMES,
        ]);
    }

    public function store(StoreSchoolRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $school = $this->setup->create($data);

        // Send welcome email to the newly created school admin
        $admin = User::withoutGlobalScopes()
            ->where('school_id', $school->id)
            ->where('role', 'school_admin')
            ->first();

        if ($admin?->email) {
            $admin->notify(new WelcomeNewUser('school_admin', $admin->username, $school->name, $data['admin_password']));
        }

        return redirect()
            ->route('super-admin.schools.show', $school)
            ->with('success', __('School ":name" created successfully.', ['name' => $school->name]));
    }

    public function show(School $school): View
    {
        $school->loadCount([
            'users as students_count' => fn ($q) => $q->withoutGlobalScopes()->where('role', 'student'),
            'users as teachers_count' => fn ($q) => $q->withoutGlobalScopes()->where('role', 'teacher'),
            'users as parents_count' => fn ($q) => $q->withoutGlobalScopes()->where('role', 'parent'),
            'users as admins_count' => fn ($q) => $q->withoutGlobalScopes()->where('role', 'school_admin'),
            'levels' => fn ($q) => $q->withoutGlobalScopes(),
            'classes' => fn ($q) => $q->withoutGlobalScopes(),
        ]);

        $primaryAdmin = User::withoutGlobalScopes()
            ->where('school_id', $school->id)
            ->where('role', 'school_admin')
            ->orderBy('id')
            ->first();

        return view('super-admin.schools.show', compact('school', 'primaryAdmin'));
    }

    public function edit(School $school): View
    {
        $primaryAdmin = User::withoutGlobalScopes()
            ->where('school_id', $school->id)
            ->where('role', 'school_admin')
            ->orderBy('id')
            ->first();

        return view('super-admin.schools.edit', compact('school', 'primaryAdmin'));
    }

    public function update(UpdateSchoolRequest $school_request, School $school): RedirectResponse
    {
        $data = $school_request->validated();

        $settings = $school->settings ?? [];
        $settings['branding'] = array_merge($settings['branding'] ?? [], [
            'primary_color' => $data['primary_color'] ?? ($settings['branding']['primary_color'] ?? '#4F46E5'),
            'secondary_color' => $data['secondary_color'] ?? ($settings['branding']['secondary_color'] ?? '#F59E0B'),
            'accent_color' => $data['accent_color'] ?? ($settings['branding']['accent_color'] ?? '#10B981'),
        ]);

        $school->update([
            'name' => $data['name'],
            'custom_domain' => $data['custom_domain'] ?? null,
            'email' => $data['email'],
            'phone' => $data['phone'] ?? null,
            'address' => $data['address'] ?? null,
            'city' => $data['city'] ?? null,
            'state' => $data['state'] ?? null,
            'country' => $data['country'] ?? null,
            'website' => $data['website'] ?? null,
            'motto' => $data['motto'] ?? null,
            'settings' => $settings,
        ]);

        return redirect()
            ->route('super-admin.schools.show', $school)
            ->with('success', __('School updated.'));
    }

    public function activate(School $school): RedirectResponse
    {
        $school->update([
            'is_active' => true,
            'deactivation_reason' => null,
            'deactivated_at' => null,
        ]);

        return back()->with('success', __('":name" activated.', ['name' => $school->name]));
    }

    public function deactivate(Request $request, School $school): RedirectResponse
    {
        $validated = $request->validate([
            'deactivation_reason' => ['required', 'string', 'max:1000'],
        ]);

        $school->update([
            'is_active' => false,
            'deactivation_reason' => $validated['deactivation_reason'],
            'deactivated_at' => now(),
        ]);

        return back()->with('success', __('":name" deactivated.', ['name' => $school->name]));
    }

    public function destroy(School $school): RedirectResponse
    {
        abort_if($school->isPlatform(), 403, 'The platform school cannot be deleted.');

        $name = $school->name;
        $school->delete();

        return redirect()
            ->route('super-admin.schools.index')
            ->with('success', __('School ":name" deleted.', ['name' => $name]));
    }

    /**
     * Reset the password for a school's admin user.
     */
    public function resetAdminPassword(Request $request, School $school): RedirectResponse
    {
        $validated = $request->validate([
            'admin_id' => ['required', 'integer'],
            'password' => ['required', 'string', Password::defaults()],
        ]);

        $admin = User::withoutGlobalScopes()
            ->where('id', $validated['admin_id'])
            ->where('school_id', $school->id)
            ->where('role', 'school_admin')
            ->firstOrFail();

        $admin->update([
            'password' => Hash::make($validated['password']),
            'must_change_password' => true,
        ]);

        return back()->with('success', __('Password reset for :name. They will be prompted to change it on next login.', ['name' => $admin->name]));
    }
}
