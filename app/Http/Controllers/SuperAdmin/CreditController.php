<?php

declare(strict_types=1);

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Http\Requests\AdjustCreditsRequest;
use App\Models\AiCreditPurchase;
use App\Models\AiCreditUsageLog;
use App\Models\AuditLog;
use App\Models\School;
use App\Services\SchoolSetupService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class CreditController extends Controller
{
    public function __construct(private readonly SchoolSetupService $setup) {}

    public function index(Request $request): View
    {
        $query = School::tenants()->orderBy('name');

        if ($search = trim((string) $request->string('search'))) {
            $query->where('name', 'like', "%{$search}%");
        }

        $schools = $query->paginate(10)->withQueryString();

        return view('super-admin.credits.index', compact('schools'));
    }

    public function adjust(AdjustCreditsRequest $request, School $school): RedirectResponse
    {
        $data = $request->validated();

        $freeDelta = (int) ($data['free_delta'] ?? 0);
        $purchasedDelta = (int) ($data['purchased_delta'] ?? 0);

        $oldFree = $school->ai_free_credits;
        $oldPurchased = $school->ai_purchased_credits;

        $this->setup->adjustCredits($school, $freeDelta, $purchasedDelta);

        $school->refresh();

        $this->auditLog($request, $school, 'school.credits_adjusted',
            ['ai_free_credits' => $oldFree, 'ai_purchased_credits' => $oldPurchased],
            [
                'ai_free_credits' => $school->ai_free_credits,
                'ai_purchased_credits' => $school->ai_purchased_credits,
                'free_delta' => $freeDelta,
                'purchased_delta' => $purchasedDelta,
            ]
        );

        return back()->with('success', __('Credit balance updated for :name.', ['name' => $school->name]));
    }

    private function auditLog(Request $request, School $school, string $action, array $old, array $new): void
    {
        AuditLog::create([
            'school_id' => $school->id,
            'user_id' => auth()->id(),
            'action' => $action,
            'entity_type' => 'school',
            'entity_id' => $school->id,
            'old_values' => $old ?: null,
            'new_values' => $new ?: null,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);
    }

    // ─────────────────────────────────────────────────────────────────
    // S13 — AI Usage Analytics
    // ─────────────────────────────────────────────────────────────────

    public function analytics(): View
    {
        // Platform-wide totals
        $totalUsed = AiCreditUsageLog::withoutGlobalScopes()->sum('credits_used');
        $quizTotal = AiCreditUsageLog::withoutGlobalScopes()->where('usage_type', 'quiz')->sum('credits_used');
        $gameTotal = AiCreditUsageLog::withoutGlobalScopes()->where('usage_type', 'game')->sum('credits_used');
        $examTotal = AiCreditUsageLog::withoutGlobalScopes()->where('usage_type', 'exam')->sum('credits_used');
        $assessmentTotal = AiCreditUsageLog::withoutGlobalScopes()->where('usage_type', 'assessment')->sum('credits_used');
        $assignmentTotal = AiCreditUsageLog::withoutGlobalScopes()->where('usage_type', 'assignment')->sum('credits_used');
        $thisMonthUsed = AiCreditUsageLog::withoutGlobalScopes()
            ->whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year)
            ->sum('credits_used');

        $totalSchoolsUsing = AiCreditUsageLog::withoutGlobalScopes()
            ->distinct('school_id')
            ->count('school_id');

        // Top 10 schools by total AI credits used (join schools for name)
        $topSchools = AiCreditUsageLog::withoutGlobalScopes()
            ->join('schools', 'ai_credit_usage_log.school_id', '=', 'schools.id')
            ->selectRaw('
                ai_credit_usage_log.school_id,
                schools.name as school_name,
                schools.slug as school_slug,
                SUM(ai_credit_usage_log.credits_used) as total_used,
                SUM(CASE WHEN ai_credit_usage_log.usage_type = "quiz"       THEN ai_credit_usage_log.credits_used ELSE 0 END) as quiz_used,
                SUM(CASE WHEN ai_credit_usage_log.usage_type = "game"       THEN ai_credit_usage_log.credits_used ELSE 0 END) as game_used,
                SUM(CASE WHEN ai_credit_usage_log.usage_type = "exam"       THEN ai_credit_usage_log.credits_used ELSE 0 END) as exam_used,
                SUM(CASE WHEN ai_credit_usage_log.usage_type = "assessment" THEN ai_credit_usage_log.credits_used ELSE 0 END) as assessment_used,
                SUM(CASE WHEN ai_credit_usage_log.usage_type = "assignment" THEN ai_credit_usage_log.credits_used ELSE 0 END) as assignment_used,
                MAX(ai_credit_usage_log.created_at) as last_used_at
            ')
            ->groupBy('ai_credit_usage_log.school_id', 'schools.name', 'schools.slug')
            ->orderByDesc('total_used')
            ->limit(10)
            ->get();

        // Monthly usage trend — last 12 months (all content types)
        $monthStart = now()->startOfMonth()->subMonths(11);

        $monthlyRaw = AiCreditUsageLog::withoutGlobalScopes()
            ->selectRaw('DATE_FORMAT(created_at, "%Y-%m") as ym, usage_type, SUM(credits_used) as total')
            ->where('created_at', '>=', $monthStart)
            ->groupBy('ym', 'usage_type')
            ->get()
            ->groupBy('ym');

        $trendLabels = [];
        $trendQuiz = [];
        $trendGame = [];
        $trendExam = [];
        $trendAssessment = [];
        $trendAssignment = [];

        for ($i = 11; $i >= 0; $i--) {
            $m = now()->startOfMonth()->subMonths($i);
            $key = $m->format('Y-m');

            $trendLabels[] = $m->format('M Y');
            $group = $monthlyRaw->get($key, collect());
            $trendQuiz[] = (int) $group->where('usage_type', 'quiz')->sum('total');
            $trendGame[] = (int) $group->where('usage_type', 'game')->sum('total');
            $trendExam[] = (int) $group->where('usage_type', 'exam')->sum('total');
            $trendAssessment[] = (int) $group->where('usage_type', 'assessment')->sum('total');
            $trendAssignment[] = (int) $group->where('usage_type', 'assignment')->sum('total');
        }

        // Total AI credits available across platform
        $totalAvailable = School::tenants()->sum(
            DB::raw('ai_free_credits + ai_purchased_credits')
        );

        return view('super-admin.credits.analytics', compact(
            'totalUsed', 'quizTotal', 'gameTotal', 'examTotal', 'assessmentTotal', 'assignmentTotal',
            'thisMonthUsed', 'totalSchoolsUsing', 'topSchools',
            'trendLabels', 'trendQuiz', 'trendGame', 'trendExam', 'trendAssessment', 'trendAssignment',
            'totalAvailable',
        ));
    }

    // ─────────────────────────────────────────────────────────────────
    // S14 — Credit Usage History
    // ─────────────────────────────────────────────────────────────────

    public function history(Request $request): View
    {
        $schools = School::tenants()->orderBy('name')->get(['id', 'name']);
        $activeTab = $request->input('tab', 'usage');

        // --- Usage log query ---
        $usageQuery = AiCreditUsageLog::withoutGlobalScopes()
            ->with(['school:id,name', 'user:id,name', 'level:id,name'])
            ->orderByDesc('created_at');

        if ($schoolId = $request->input('school_id')) {
            $usageQuery->where('school_id', (int) $schoolId);
        }
        if ($type = $request->input('usage_type')) {
            $usageQuery->where('usage_type', $type);
        }
        if ($from = $request->input('from')) {
            $usageQuery->whereDate('created_at', '>=', $from);
        }
        if ($to = $request->input('to')) {
            $usageQuery->whereDate('created_at', '<=', $to);
        }

        $usageLogs = $usageQuery->paginate(30, ['*'], 'usage_page')->withQueryString();

        // --- Purchases query ---
        $purchaseQuery = AiCreditPurchase::withoutGlobalScopes()
            ->with(['school:id,name', 'purchaser:id,name'])
            ->orderByDesc('created_at');

        if ($schoolId = $request->input('school_id')) {
            $purchaseQuery->where('school_id', (int) $schoolId);
        }
        if ($status = $request->input('status')) {
            $purchaseQuery->where('status', $status);
        }
        if ($from = $request->input('from')) {
            $purchaseQuery->whereDate('created_at', '>=', $from);
        }
        if ($to = $request->input('to')) {
            $purchaseQuery->whereDate('created_at', '<=', $to);
        }

        $purchases = $purchaseQuery->paginate(30, ['*'], 'purchase_page')->withQueryString();

        // Summary stats
        $totalPurchasedCredits = AiCreditPurchase::withoutGlobalScopes()
            ->where('status', 'completed')
            ->sum('credits');
        $totalRevenue = AiCreditPurchase::withoutGlobalScopes()
            ->where('status', 'completed')
            ->sum('amount_naira');

        return view('super-admin.credits.history', compact(
            'schools', 'usageLogs', 'purchases',
            'totalPurchasedCredits', 'totalRevenue', 'activeTab',
        ));
    }
}
