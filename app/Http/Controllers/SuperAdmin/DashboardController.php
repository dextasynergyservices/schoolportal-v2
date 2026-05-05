<?php

declare(strict_types=1);

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\AiCreditPurchase;
use App\Models\AiCreditUsageLog;
use App\Models\AuditLog;
use App\Models\School;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(): View
    {
        $now = now();
        $startOfMonth = $now->copy()->startOfMonth();
        $startOfLastMonth = $now->copy()->subMonth()->startOfMonth();
        $endOfLastMonth = $now->copy()->subMonth()->endOfMonth();

        // ── Cached aggregate stats (10-min TTL, busted on key events via observers) ──
        $stats = Cache::remember('platform:dashboard:stats', now()->addMinutes(10), function () use ($startOfMonth, $startOfLastMonth, $endOfLastMonth, $now) {
            return [
                'totalSchools' => School::tenants()->count(),
                'activeSchools' => School::tenants()->where('is_active', true)->count(),
                'totalStudents' => User::withoutGlobalScopes()->where('role', 'student')->count(),
                'totalTeachers' => User::withoutGlobalScopes()->where('role', 'teacher')->count(),
                'totalParents' => User::withoutGlobalScopes()->where('role', 'parent')->count(),
                'totalAdmins' => User::withoutGlobalScopes()->where('role', 'school_admin')->count(),
                'newSchoolsThisMonth' => School::tenants()->where('created_at', '>=', $startOfMonth)->count(),
                'newStudentsThisMonth' => User::withoutGlobalScopes()->where('role', 'student')->where('created_at', '>=', $startOfMonth)->count(),
                'newTeachersThisMonth' => User::withoutGlobalScopes()->where('role', 'teacher')->where('created_at', '>=', $startOfMonth)->count(),
                'totalRevenue' => (float) AiCreditPurchase::withoutGlobalScopes()->where('status', 'completed')->sum('amount_naira'),
                'monthlyRevenue' => (float) AiCreditPurchase::withoutGlobalScopes()->where('status', 'completed')->where('created_at', '>=', $startOfMonth)->sum('amount_naira'),
                'lastMonthRevenue' => (float) AiCreditPurchase::withoutGlobalScopes()->where('status', 'completed')->whereBetween('created_at', [$startOfLastMonth, $endOfLastMonth])->sum('amount_naira'),
                'creditsUsedThisMonth' => (int) AiCreditUsageLog::withoutGlobalScopes()->where('created_at', '>=', $startOfMonth)->sum('credits_used'),
                'totalPurchasedCredits' => (int) School::tenants()->sum('ai_credits_total_purchased'),
                'pendingPayments' => AiCreditPurchase::withoutGlobalScopes()->where('status', 'pending')->count(),
                'loginsThisWeek' => User::withoutGlobalScopes()->where('last_login_at', '>=', $now->copy()->startOfWeek())->count(),
                'loginsThisMonth' => User::withoutGlobalScopes()->where('last_login_at', '>=', $startOfMonth)->count(),
            ];
        });

        // Derived (cheap, no need to cache)
        $inactiveSchools = $stats['totalSchools'] - $stats['activeSchools'];
        $revenueChangePercent = $stats['lastMonthRevenue'] > 0
            ? round((($stats['monthlyRevenue'] - $stats['lastMonthRevenue']) / $stats['lastMonthRevenue']) * 100, 1)
            : ($stats['monthlyRevenue'] > 0 ? 100.0 : 0.0);

        extract($stats);

        // ── Health alerts & lists — cached separately (short-lived, lower cardinality) ──
        // Cache only scalar data (IDs, slugs, types) — never Eloquent model instances.
        // Eloquent models serialised into the file/Redis cache can fail to deserialise
        // correctly in PHP 8.4 (producing false / __PHP_Incomplete_Class), which causes
        // a 500 on the second request when the cache is hit. After retrieval we re-hydrate
        // fresh model instances with one cheap whereIn query each.
        [$rawAlerts, $rawTopSchools] = Cache::remember(
            'platform:dashboard:health.v2',
            now()->addMinutes(10),
            function () use ($now): array {
                $schoolsNoSession = School::tenants()
                    ->where('is_active', true)
                    ->whereDoesntHave('academicSessions', fn ($q) => $q->withoutGlobalScopes()->where('is_current', true))
                    ->limit(10)->get(['id', 'name', 'slug']);

                $schoolsNoRecentLogin = School::tenants()
                    ->where('is_active', true)
                    ->whereDoesntHave('users', fn ($q) => $q->withoutGlobalScopes()->where('last_login_at', '>=', $now->copy()->subDays(30)))
                    ->limit(10)->get(['id', 'name', 'slug']);

                $schoolsZeroStudents = School::tenants()
                    ->where('is_active', true)
                    ->whereDoesntHave('users', fn ($q) => $q->withoutGlobalScopes()->where('role', 'student'))
                    ->limit(10)->get(['id', 'name', 'slug']);

                $topSchoolsQuery = School::tenants()
                    ->where('is_active', true)
                    ->withCount(['users as student_count' => fn ($q) => $q->withoutGlobalScopes()->where('role', 'student')])
                    ->orderByDesc('student_count')
                    ->limit(5)->get(['id', 'name', 'slug']);

                // Flatten to scalar-only arrays — safe to serialise in any cache driver.
                $alertRows = array_merge(
                    $schoolsNoSession->map(fn ($s) => ['id' => $s->id, 'name' => $s->name, 'slug' => $s->slug, 'type' => 'no_session'])->all(),
                    $schoolsNoRecentLogin->map(fn ($s) => ['id' => $s->id, 'name' => $s->name, 'slug' => $s->slug, 'type' => 'no_login'])->all(),
                    $schoolsZeroStudents->map(fn ($s) => ['id' => $s->id, 'name' => $s->name, 'slug' => $s->slug, 'type' => 'no_students'])->all(),
                );

                $topRows = $topSchoolsQuery->map(fn ($s) => [
                    'id' => (int) $s->id,
                    'name' => $s->name,
                    'slug' => $s->slug,
                    'student_count' => (int) ($s->student_count ?? 0),
                ])->all();

                return [$alertRows, $topRows];
            }
        );

        // Re-hydrate School models from a fresh DB query so the view receives real
        // Eloquent instances (needed for route() model binding, ->name, ->slug, etc.).
        $alertSchoolIds = collect($rawAlerts)->pluck('id')->unique()->filter()->values()->all();
        $alertSchoolMap = $alertSchoolIds
            ? School::withoutGlobalScopes()->whereIn('id', $alertSchoolIds)->get(['id', 'name', 'slug'])->keyBy('id')
            : collect();

        $healthAlerts = collect($rawAlerts)
            ->map(fn (array $row) => [
                'school' => $alertSchoolMap->get($row['id']),
                'type' => $row['type'],
                'message' => match ($row['type']) {
                    'no_session' => __('No active session/term'),
                    'no_login' => __('No logins in 30+ days'),
                    default => __('Zero students'),
                },
            ])
            ->filter(fn (array $a) => $a['school'] !== null)
            ->values();

        $topSchoolIds = collect($rawTopSchools)->pluck('id')->all();
        $topSchoolMap = $topSchoolIds
            ? School::withoutGlobalScopes()->whereIn('id', $topSchoolIds)->get(['id', 'name', 'slug'])->keyBy('id')
            : collect();

        $topSchools = collect($rawTopSchools)
            ->map(function (array $row) use ($topSchoolMap): ?School {
                $model = $topSchoolMap->get($row['id']);
                if ($model) {
                    $model->setAttribute('student_count', $row['student_count']);
                }

                return $model;
            })
            ->filter()
            ->values();

        // ── Recent activity lists — not cached (always fresh) ────────────────
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
