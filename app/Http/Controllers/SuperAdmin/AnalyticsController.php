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

        $schoolsData = $this->seriesData($monthKeys, $start, $end, 'schools');
        $studentsData = $this->seriesData($monthKeys, $start, $end, 'students');
        $revenueData = $this->seriesData($monthKeys, $start, $end, 'revenue');
        $creditsData = $this->seriesData($monthKeys, $start, $end, 'credits');

        $periodSchools = (int) array_sum($schoolsData);
        $periodStudents = (int) array_sum($studentsData);
        $periodRevenue = (float) array_sum($revenueData);
        $periodCredits = (int) array_sum($creditsData);

        $totalSchools = School::tenants()->count();
        $totalStudents = User::withoutGlobalScopes()->where('role', 'student')->count();

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
            'start',
            'end',
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
    private function seriesData(Collection $monthKeys, CarbonInterface $start, CarbonInterface $end, string $series): array
    {
        $raw = match ($series) {
            'schools' => School::tenants()
                ->selectRaw("DATE_FORMAT(created_at, '%Y-%m') as month, COUNT(*) as cnt")
                ->whereBetween('created_at', [$start, $end])
                ->groupBy('month')
                ->get()
                ->pluck('cnt', 'month'),

            'students' => User::withoutGlobalScopes()
                ->selectRaw("DATE_FORMAT(created_at, '%Y-%m') as month, COUNT(*) as cnt")
                ->where('role', 'student')
                ->whereBetween('created_at', [$start, $end])
                ->groupBy('month')
                ->get()
                ->pluck('cnt', 'month'),

            'revenue' => AiCreditPurchase::withoutGlobalScopes()
                ->selectRaw("DATE_FORMAT(created_at, '%Y-%m') as month, SUM(amount_naira) as total")
                ->where('status', 'completed')
                ->whereBetween('created_at', [$start, $end])
                ->groupBy('month')
                ->get()
                ->pluck('total', 'month'),

            'credits' => AiCreditUsageLog::withoutGlobalScopes()
                ->selectRaw("DATE_FORMAT(created_at, '%Y-%m') as month, SUM(credits_used) as total")
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
}
