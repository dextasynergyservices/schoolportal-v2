<?php

declare(strict_types=1);

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\AiCreditPurchase;
use App\Models\AiCreditUsageLog;
use App\Models\School;
use App\Models\User;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class AnalyticsController extends Controller
{
    /**
     * Display the analytics dashboard.
     */
    public function __invoke(Request $request): View
    {
        [$start, $end, $range, , $mode] = $this->resolveRange($request);

        $monthKeys = $this->buildMonthKeys($start, $end);
        $months = $monthKeys->count();
        $monthLabels = $monthKeys->map(fn (string $m) => Carbon::parse($m.'-01')->format("M 'y"));

        // ── Geo filter ────────────────────────────────────────────────
        $geoLocation = $request->string('geo_location')->toString();

        $filteredSchoolIds = null;
        $filteredSchoolCount = null;

        if ($geoLocation !== '') {
            $filteredSchoolIds = School::tenants()
                ->where(function ($q) use ($geoLocation) {
                    $q->where('city', 'like', "%{$geoLocation}%")
                        ->orWhere('state', 'like', "%{$geoLocation}%")
                        ->orWhere('country', 'like', "%{$geoLocation}%");
                })
                ->pluck('id')
                ->all();
            $filteredSchoolCount = count($filteredSchoolIds);
        }

        $schoolsData = $this->seriesData($monthKeys, $start, $end, 'schools', $filteredSchoolIds);
        $studentsData = $this->seriesData($monthKeys, $start, $end, 'students', $filteredSchoolIds);
        $revenueData = $this->seriesData($monthKeys, $start, $end, 'revenue', $filteredSchoolIds);
        $creditsData = $this->seriesData($monthKeys, $start, $end, 'credits', $filteredSchoolIds);

        $periodSchools = (int) array_sum($schoolsData);
        $periodStudents = (int) array_sum($studentsData);
        $periodRevenue = (float) array_sum($revenueData);
        $periodCredits = (int) array_sum($creditsData);

        $totalSchools = $filteredSchoolCount ?? School::tenants()->count();
        $totalStudents = User::withoutGlobalScopes()
            ->where('role', 'student')
            ->when($filteredSchoolIds !== null, fn ($q) => $q->whereIn('school_id', $filteredSchoolIds))
            ->count();

        // ── Geographic breakdown (always all-time, not scoped by geo filter) ─
        $geoData = Cache::remember('platform:analytics:geo', now()->addMinutes(30), function () {
            return $this->geoBreakdown();
        });

        // ── Cohort analysis (skip cache when geo filter is active) ────
        $cohortData = ($filteredSchoolIds !== null)
            ? $this->cohortAnalysis($monthKeys, $start, $end, $filteredSchoolIds)
            : Cache::remember("platform:analytics:cohort:{$range}", now()->addMinutes(60), function () use ($start, $end, $monthKeys) {
                return $this->cohortAnalysis($monthKeys, $start, $end);
            });

        $nigerianStates = config('schoolportal.nigerian_states');

        return view('super-admin.analytics', compact(
            'range',
            'mode',
            'months',
            'monthLabels',
            'schoolsData',
            'studentsData',
            'revenueData',
            'creditsData',
            'periodSchools',
            'periodStudents',
            'periodRevenue',
            'periodCredits',
            'totalSchools',
            'totalStudents',
            'geoData',
            'cohortData',
            'start',
            'end',
            'geoLocation',
            'filteredSchoolCount',
        ));
    }

    /**
     * Export analytics data as a CSV download.
     */
    public function export(Request $request): Response
    {
        [$start, $end] = $this->resolveRange($request);

        $monthKeys = $this->buildMonthKeys($start, $end);
        $schoolsData = $this->seriesData($monthKeys, $start, $end, 'schools');
        $studentsData = $this->seriesData($monthKeys, $start, $end, 'students');
        $revenueData = $this->seriesData($monthKeys, $start, $end, 'revenue');
        $creditsData = $this->seriesData($monthKeys, $start, $end, 'credits');

        $rows = [];
        $rows[] = ['Month', 'New Schools', 'New Students', 'Revenue (NGN)', 'AI Credits Used'];

        foreach ($monthKeys as $i => $key) {
            $rows[] = [
                Carbon::parse($key.'-01')->format('M Y'),
                $schoolsData[$i] ?? 0,
                $studentsData[$i] ?? 0,
                $revenueData[$i] ?? 0,
                $creditsData[$i] ?? 0,
            ];
        }

        $rows[] = [
            'TOTAL',
            array_sum($schoolsData),
            array_sum($studentsData),
            array_sum($revenueData),
            array_sum($creditsData),
        ];

        $filename = 'analytics-'.$start->format('Y-m').'-to-'.$end->format('Y-m').'.csv';

        $csv = '';
        foreach ($rows as $row) {
            $csv .= implode(',', array_map(
                fn ($v) => '"'.str_replace('"', '""', (string) $v).'"',
                $row,
            ))."\r\n";
        }

        return response($csv, 200, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="'.$filename.'"',
            'Cache-Control' => 'no-store, no-cache',
        ]);
    }

    // ── Private helpers ──────────────────────────────────────────────────────

    /**
     * Resolve the date range from the request.
     *
     * Supports:
     *   - Preset shortcuts:  ?range=3m|6m|12m   (default 12m)
     *   - Custom date range: ?range=custom&from=YYYY-MM-DD&to=YYYY-MM-DD
     *
     * @return array{Carbon, Carbon, string, int, string} [start, end, range, months, mode]
     */
    private function resolveRange(Request $request): array
    {
        $range = $request->get('range', '12m');

        if ($range === 'custom') {
            $from = $request->get('from');
            $to = $request->get('to');

            if ($from && $to) {
                try {
                    $start = Carbon::parse($from)->startOfMonth();
                    $end = Carbon::parse($to)->endOfMonth();

                    // Clamp: end ≤ today, start ≤ end, max 24 months back
                    $end = $end->min(now()->endOfMonth());
                    $start = $start->min($end);
                    $floor = $end->copy()->subMonths(23)->startOfMonth();
                    $start = $start->max($floor);

                    return [$start, $end, 'custom', $start->diffInMonths($end) + 1, 'custom'];
                } catch (\Throwable) {
                    // Fall through to default
                }
            }

            $range = '12m';
        }

        if (! in_array($range, ['3m', '6m', '12m'], true)) {
            $range = '12m';
        }

        $months = (int) str_replace('m', '', $range);
        $start = now()->startOfMonth()->subMonths($months - 1);
        $end = now()->endOfMonth();

        return [$start, $end, $range, $months, 'preset'];
    }

    /**
     * Build an ordered collection of YYYY-MM strings from $start to $end.
     *
     * @return Collection<int, string>
     */
    private function buildMonthKeys(CarbonInterface $start, CarbonInterface $end): Collection
    {
        $keys = collect();
        $current = Carbon::instance($start)->startOfMonth();
        $endSom = Carbon::instance($end)->startOfMonth();

        while ($current->lte($endSom)) {
            $keys->push($current->format('Y-m'));
            $current->addMonth();
        }

        return $keys;
    }

    /**
     * Fetch monthly aggregated data for one series, left-joined against $monthKeys.
     *
     * @param  Collection<int, string>  $monthKeys
     * @return array<int, int|float>
     */
    private function seriesData(Collection $monthKeys, CarbonInterface $start, CarbonInterface $end, string $series, ?array $schoolIds = null): array
    {
        $raw = match ($series) {
            'schools' => School::tenants()
                ->selectRaw("DATE_FORMAT(created_at, '%Y-%m') as month, COUNT(*) as cnt")
                ->when($schoolIds !== null, fn ($q) => $q->whereIn('id', $schoolIds))
                ->whereBetween('created_at', [$start, $end])
                ->groupBy('month')
                ->get()
                ->pluck('cnt', 'month'),

            'students' => User::withoutGlobalScopes()
                ->selectRaw("DATE_FORMAT(created_at, '%Y-%m') as month, COUNT(*) as cnt")
                ->where('role', 'student')
                ->when($schoolIds !== null, fn ($q) => $q->whereIn('school_id', $schoolIds))
                ->whereBetween('created_at', [$start, $end])
                ->groupBy('month')
                ->get()
                ->pluck('cnt', 'month'),

            'revenue' => AiCreditPurchase::withoutGlobalScopes()
                ->selectRaw("DATE_FORMAT(created_at, '%Y-%m') as month, SUM(amount_naira) as total")
                ->where('status', 'completed')
                ->when($schoolIds !== null, fn ($q) => $q->whereIn('school_id', $schoolIds))
                ->whereBetween('created_at', [$start, $end])
                ->groupBy('month')
                ->get()
                ->pluck('total', 'month'),

            'credits' => AiCreditUsageLog::withoutGlobalScopes()
                ->selectRaw("DATE_FORMAT(created_at, '%Y-%m') as month, SUM(credits_used) as total")
                ->when($schoolIds !== null, fn ($q) => $q->whereIn('school_id', $schoolIds))
                ->whereBetween('created_at', [$start, $end])
                ->groupBy('month')
                ->get()
                ->pluck('total', 'month'),

            default => collect(),
        };

        return $monthKeys->map(function (string $m) use ($raw, $series) {
            $value = $raw[$m] ?? 0;

            return $series === 'revenue' ? (float) $value : (int) $value;
        })->values()->toArray();
    }

    /**
     * Geographic breakdown — schools grouped by state, then city within each state.
     *
     * Returns:
     * [
     *   'by_state' => [['state' => 'Lagos', 'count' => 12], ...],   // sorted desc
     *   'by_city'  => [['city' => 'Ikeja', 'state' => 'Lagos', 'count' => 5], ...],
     * ]
     *
     * @return array{by_state: array<int, array{state: string, count: int}>, by_city: array<int, array{city: string, state: string, count: int}>}
     */
    private function geoBreakdown(): array
    {
        $byState = School::tenants()
            ->select('state', DB::raw('COUNT(*) as count'))
            ->whereNotNull('state')
            ->where('state', '!=', '')
            ->groupBy('state')
            ->orderByDesc('count')
            ->limit(15)
            ->get()
            ->map(fn ($r) => ['state' => (string) $r->state, 'count' => (int) $r->count])
            ->values()
            ->toArray();

        $byCity = School::tenants()
            ->select('city', 'state', DB::raw('COUNT(*) as count'))
            ->whereNotNull('city')
            ->where('city', '!=', '')
            ->groupBy('city', 'state')
            ->orderByDesc('count')
            ->limit(20)
            ->get()
            ->map(fn ($r) => ['city' => (string) $r->city, 'state' => (string) $r->state, 'count' => (int) $r->count])
            ->values()
            ->toArray();

        return ['by_state' => $byState, 'by_city' => $byCity];
    }

    /**
     * Cohort analysis — for each month in the window, how many schools that joined
     * that month were still active (had at least one user login) 3 months later?
     *
     * Returns an array of cohort rows:
     * [
     *   ['cohort' => 'Feb '25', 'joined' => 4, 'active_3m' => 3, 'retention' => 75.0],
     *   ...
     * ]
     * Cohorts within 3 months of today are excluded (too early to measure).
     *
     * @param  Collection<int, string>  $monthKeys
     * @return array<int, array{cohort: string, joined: int, active_3m: int, retention: float}>
     */
    private function cohortAnalysis(Collection $monthKeys, CarbonInterface $start, CarbonInterface $end, ?array $schoolIds = null): array
    {
        $cutoff = now()->subMonths(3)->startOfMonth();
        $rows = [];

        // Fetch school IDs with their creation month in one query
        $schools = School::tenants()
            ->select('id', DB::raw("DATE_FORMAT(created_at, '%Y-%m') as join_month"))
            ->when($schoolIds !== null, fn ($q) => $q->whereIn('id', $schoolIds))
            ->whereBetween('created_at', [$start, $end])
            ->get()
            ->groupBy('join_month');

        foreach ($monthKeys as $monthKey) {
            $cohortMonth = Carbon::parse($monthKey.'-01');

            // Skip cohorts less than 3 months ago — retention is not yet measurable
            if ($cohortMonth->gte($cutoff)) {
                continue;
            }

            $schoolsInCohort = $schools->get($monthKey, collect());
            $joined = $schoolsInCohort->count();

            if ($joined === 0) {
                continue;
            }

            $ids = $schoolsInCohort->pluck('id');
            $retentionWindowStart = $cohortMonth->copy()->addMonths(3)->startOfMonth();
            $retentionWindowEnd = $cohortMonth->copy()->addMonths(3)->endOfMonth();

            // Count how many of these schools had at least one user login during month+3
            $active = User::withoutGlobalScopes()
                ->whereIn('school_id', $ids)
                ->whereBetween('last_login_at', [$retentionWindowStart, $retentionWindowEnd])
                ->distinct('school_id')
                ->count('school_id');

            $rows[] = [
                'cohort' => $cohortMonth->format("M 'y"),
                'joined' => $joined,
                'active_3m' => $active,
                'retention' => $joined > 0 ? round(($active / $joined) * 100, 1) : 0.0,
            ];
        }

        return $rows;
    }
}
