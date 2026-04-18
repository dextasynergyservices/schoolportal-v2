<?php

declare(strict_types=1);

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\AiCreditPurchase;
use App\Models\AiCreditUsageLog;
use App\Models\AuditLog;
use App\Models\School;
use App\Models\User;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(): View
    {
        $now = now();
        $startOfMonth = $now->copy()->startOfMonth();
        $startOfLastMonth = $now->copy()->subMonth()->startOfMonth();
        $endOfLastMonth = $now->copy()->subMonth()->endOfMonth();

        // ── Core Counts ──────────────────────────────────────────────
        $totalSchools = School::tenants()->count();
        $activeSchools = School::tenants()->where('is_active', true)->count();
        $inactiveSchools = $totalSchools - $activeSchools;

        $totalStudents = User::withoutGlobalScopes()->where('role', 'student')->count();
        $totalTeachers = User::withoutGlobalScopes()->where('role', 'teacher')->count();
        $totalParents = User::withoutGlobalScopes()->where('role', 'parent')->count();
        $totalAdmins = User::withoutGlobalScopes()->where('role', 'school_admin')->count();

        // ── Growth Indicators (this month) ───────────────────────────
        $newSchoolsThisMonth = School::tenants()
            ->where('created_at', '>=', $startOfMonth)->count();
        $newStudentsThisMonth = User::withoutGlobalScopes()
            ->where('role', 'student')
            ->where('created_at', '>=', $startOfMonth)->count();
        $newTeachersThisMonth = User::withoutGlobalScopes()
            ->where('role', 'teacher')
            ->where('created_at', '>=', $startOfMonth)->count();

        // ── Revenue ──────────────────────────────────────────────────
        $totalRevenue = AiCreditPurchase::withoutGlobalScopes()
            ->where('status', 'completed')
            ->sum('amount_naira');

        $monthlyRevenue = AiCreditPurchase::withoutGlobalScopes()
            ->where('status', 'completed')
            ->where('created_at', '>=', $startOfMonth)
            ->sum('amount_naira');

        $lastMonthRevenue = AiCreditPurchase::withoutGlobalScopes()
            ->where('status', 'completed')
            ->whereBetween('created_at', [$startOfLastMonth, $endOfLastMonth])
            ->sum('amount_naira');

        $revenueChangePercent = $lastMonthRevenue > 0
            ? round((((float) $monthlyRevenue - (float) $lastMonthRevenue) / (float) $lastMonthRevenue) * 100, 1)
            : ($monthlyRevenue > 0 ? 100.0 : 0.0);

        // ── AI Credits ───────────────────────────────────────────────
        $creditsUsedThisMonth = (int) AiCreditUsageLog::withoutGlobalScopes()
            ->where('created_at', '>=', $startOfMonth)
            ->sum('credits_used');

        $totalPurchasedCredits = (int) School::tenants()->sum('ai_credits_total_purchased');

        $pendingPayments = AiCreditPurchase::withoutGlobalScopes()
            ->where('status', 'pending')
            ->count();

        // ── Login Activity ───────────────────────────────────────────
        $loginsThisWeek = User::withoutGlobalScopes()
            ->where('last_login_at', '>=', $now->copy()->startOfWeek())
            ->count();

        $loginsThisMonth = User::withoutGlobalScopes()
            ->where('last_login_at', '>=', $startOfMonth)
            ->count();

        // ── School Health Alerts ─────────────────────────────────────
        $schoolsNoSession = School::tenants()
            ->where('is_active', true)
            ->whereDoesntHave('academicSessions', fn ($q) => $q->withoutGlobalScopes()->where('is_current', true))
            ->limit(10)
            ->get(['id', 'name', 'slug']);

        $schoolsNoRecentLogin = School::tenants()
            ->where('is_active', true)
            ->whereDoesntHave('users', fn ($q) => $q->withoutGlobalScopes()->where('last_login_at', '>=', $now->copy()->subDays(30)))
            ->limit(10)
            ->get(['id', 'name', 'slug']);

        $schoolsZeroStudents = School::tenants()
            ->where('is_active', true)
            ->whereDoesntHave('users', fn ($q) => $q->withoutGlobalScopes()->where('role', 'student'))
            ->limit(10)
            ->get(['id', 'name', 'slug']);

        $healthAlerts = collect();
        foreach ($schoolsNoSession as $s) {
            $healthAlerts->push(['school' => $s, 'type' => 'no_session', 'message' => __('No active session/term')]);
        }
        foreach ($schoolsNoRecentLogin as $s) {
            $healthAlerts->push(['school' => $s, 'type' => 'no_login', 'message' => __('No logins in 30+ days')]);
        }
        foreach ($schoolsZeroStudents as $s) {
            $healthAlerts->push(['school' => $s, 'type' => 'no_students', 'message' => __('Zero students')]);
        }

        // ── Top Schools by Student Count ─────────────────────────────
        $topSchools = School::tenants()
            ->where('is_active', true)
            ->withCount(['users as student_count' => fn ($q) => $q->withoutGlobalScopes()->where('role', 'student')])
            ->orderByDesc('student_count')
            ->limit(5)
            ->get(['id', 'name', 'slug']);

        // ── Lists ────────────────────────────────────────────────────
        $recentSchools = School::tenants()
            ->withCount(['users as student_count' => fn ($q) => $q->withoutGlobalScopes()->where('role', 'student')])
            ->orderByDesc('created_at')
            ->limit(5)->get();

        $recentActivity = AuditLog::withoutGlobalScopes()
            ->with([
                'user' => fn ($q) => $q->withoutGlobalScopes(),
                'school:id,name',
            ])
            ->orderByDesc('created_at')
            ->limit(10)->get();

        $recentPurchases = AiCreditPurchase::withoutGlobalScopes()
            ->with([
                'school:id,name',
                'purchaser' => fn ($q) => $q->withoutGlobalScopes(),
            ])
            ->orderByDesc('created_at')
            ->limit(5)->get();

        return view('super-admin.dashboard', compact(
            'totalSchools', 'activeSchools', 'inactiveSchools',
            'totalStudents', 'totalTeachers', 'totalParents', 'totalAdmins',
            'newSchoolsThisMonth', 'newStudentsThisMonth', 'newTeachersThisMonth',
            'totalRevenue', 'monthlyRevenue', 'lastMonthRevenue', 'revenueChangePercent',
            'creditsUsedThisMonth', 'totalPurchasedCredits', 'pendingPayments',
            'loginsThisWeek', 'loginsThisMonth',
            'healthAlerts', 'topSchools',
            'recentSchools', 'recentActivity', 'recentPurchases',
        ));
    }
}
