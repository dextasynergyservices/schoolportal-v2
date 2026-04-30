<?php

declare(strict_types=1);

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\School;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ImpersonateController extends Controller
{
    /**
     * Start impersonating a school by logging in as their primary active admin.
     * Only callable by super_admin (enforced by route group middleware).
     */
    public function start(Request $request, School $school): RedirectResponse
    {
        if (! $school->is_active) {
            return back()->with('error', __('Cannot impersonate an inactive school.'));
        }

        $admin = User::withoutGlobalScopes()
            ->where('school_id', $school->id)
            ->where('role', 'school_admin')
            ->where('is_active', true)
            ->oldest()
            ->first();

        if (! $admin) {
            return back()->with('error', __('No active admin found for ":name". Create one first.', ['name' => $school->name]));
        }

        $originalId = auth()->id();

        AuditLog::create([
            'school_id' => $school->id,
            'user_id' => $originalId,
            'action' => 'school.impersonation_started',
            'entity_type' => 'user',
            'entity_id' => $admin->id,
            'old_values' => null,
            'new_values' => ['admin_id' => $admin->id, 'admin_name' => $admin->name, 'admin_username' => $admin->username],
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        $request->session()->put('impersonating_original_id', $originalId);

        // Use Auth::login() with the already-fetched model instead of loginUsingId().
        // loginUsingId() internally calls retrieveById() which runs through the
        // BelongsToTenant global scope — filtering by current.school (platform school).
        // Since the target admin belongs to a different school, retrieveById() returns null
        // and loginUsingId() silently fails, leaving the super admin in the session.
        Auth::login($admin);

        // Bind the school in session so ResolveTenant picks it up on the next request
        $request->session()->put('school_id', $school->id);

        return redirect()->route('admin.dashboard')
            ->with('success', __('Now viewing ":school" as :admin.', [
                'school' => $school->name,
                'admin' => $admin->name,
            ]));
    }

    /**
     * Stop impersonating and return to the original super_admin account.
     * Accessible to any authenticated user (not restricted to super_admin,
     * since during impersonation the auth user is school_admin).
     */
    public function stop(Request $request): RedirectResponse
    {
        $originalId = $request->session()->pull('impersonating_original_id');

        if (! $originalId) {
            // Not impersonating — just go to dashboard
            return redirect()->route('dashboard');
        }

        $original = User::withoutGlobalScopes()->find($originalId);

        if (! $original || $original->role !== 'super_admin') {
            // Safety: stored ID is no longer valid — force logout
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('login')
                ->withErrors(['email' => __('Your session has expired. Please log in again.')]);
        }

        $impersonatedSchoolId = auth()->user()?->school_id;

        AuditLog::create([
            'school_id' => $impersonatedSchoolId ?? $original->school_id,
            'user_id' => $originalId,
            'action' => 'school.impersonation_stopped',
            'entity_type' => 'user',
            'entity_id' => auth()->id(),
            'old_values' => null,
            'new_values' => null,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        // Same reason: use Auth::login() with the fetched model instead of loginUsingId()
        // to avoid the BelongsToTenant scope filtering out the super admin user.
        Auth::login($original);

        $request->session()->forget('school_id');

        $school = $impersonatedSchoolId
            ? School::withoutGlobalScopes()->find($impersonatedSchoolId)
            : null;

        $target = $school
            ? route('super-admin.schools.show', $school)
            : route('super-admin.dashboard');

        return redirect($target)
            ->with('success', __('Returned to your super admin account.'));
    }
}
