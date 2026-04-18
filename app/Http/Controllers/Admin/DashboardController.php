<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Assignment;
use App\Models\AuditLog;
use App\Models\Notice;
use App\Models\Result;
use App\Models\SchoolClass;
use App\Models\SchoolLevel;
use App\Models\TeacherAction;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(): View
    {
        $school = app('current.school');
        $currentSession = $school->currentSession();
        $currentTerm = $school->currentTerm();

        // ── Core Counts (cached for 5 minutes) ──────────────────────
        $cacheKey = "school:{$school->id}:dashboard:stats";
        $stats = Cache::remember($cacheKey, now()->addMinutes(5), function () {
            return [
                'totalStudents' => User::where('role', 'student')->count(),
                'maleStudents' => User::where('role', 'student')->where('gender', 'male')->count(),
                'femaleStudents' => User::where('role', 'student')->where('gender', 'female')->count(),
                'totalTeachers' => User::where('role', 'teacher')->count(),
                'totalParents' => User::where('role', 'parent')->count(),
                'totalClasses' => SchoolClass::where('is_active', true)->count(),
            ];
        });

        $totalStudents = $stats['totalStudents'];
        $maleStudents = $stats['maleStudents'];
        $femaleStudents = $stats['femaleStudents'];
        $totalTeachers = $stats['totalTeachers'];
        $totalParents = $stats['totalParents'];
        $totalClasses = $stats['totalClasses'];

        // ── AI Credits Balance ───────────────────────────────────────
        $aiFreeCredits = $school->ai_free_credits;
        $aiPurchasedCredits = $school->ai_purchased_credits;
        $aiTotalCredits = $school->aiCreditsBalance();
        $aiFreeResetDate = $school->ai_free_credits_reset_at;

        // ── Students by Level ────────────────────────────────────────

        $studentsByLevel = User::where('role', 'student')
            ->where('is_active', true)
            ->whereNotNull('level_id')
            ->selectRaw('level_id, count(*) as total')
            ->groupBy('level_id')
            ->pluck('total', 'level_id');

        $levelBreakdown = SchoolLevel::where('is_active', true)
            ->orderBy('sort_order')
            ->get(['id', 'name'])
            ->map(fn ($level) => [
                'name' => $level->name,
                'count' => $studentsByLevel[$level->id] ?? 0,
            ]);

        // ── Class Occupancy (classes with capacity set) ──────────────
        $classOccupancy = SchoolClass::where('is_active', true)
            ->whereNotNull('capacity')
            ->where('capacity', '>', 0)
            ->withCount('students')
            ->with('level:id,name')
            ->orderBy('sort_order')
            ->limit(10)
            ->get(['id', 'name', 'capacity', 'level_id']);

        // ── Term Progress: Results Upload Tracker ────────────────────
        $resultsUploaded = 0;
        $resultsTotal = $totalStudents; // one result per student
        if ($currentSession && $currentTerm) {
            $resultsUploaded = Result::where('session_id', $currentSession->id)
                ->where('term_id', $currentTerm->id)
                ->where('status', 'approved')
                ->count();
        }

        // ── Assignments Coverage by Class ────────────────────────────
        $weeksPerTerm = $school->setting('academic.weeks_per_term', 12);
        $assignmentCoverage = collect();
        if ($currentSession && $currentTerm) {
            $classes = SchoolClass::where('is_active', true)
                ->with('level:id,name')
                ->orderBy('sort_order')
                ->get(['id', 'name', 'level_id']);

            $assignmentsByClass = Assignment::where('session_id', $currentSession->id)
                ->where('term_id', $currentTerm->id)
                ->where('status', 'approved')
                ->selectRaw('class_id, count(*) as uploaded_weeks')
                ->groupBy('class_id')
                ->pluck('uploaded_weeks', 'class_id');

            $assignmentCoverage = $classes->map(fn ($cls) => [
                'name' => $cls->name,
                'level' => $cls->level?->name,
                'uploaded' => $assignmentsByClass[$cls->id] ?? 0,
                'total' => $weeksPerTerm,
            ]);
        }

        // ── Quick Stats for Current Term ─────────────────────────────
        $termResultsCount = 0;
        $termAssignmentsCount = 0;
        $termNoticesCount = 0;
        if ($currentSession && $currentTerm) {
            $termResultsCount = Result::where('session_id', $currentSession->id)
                ->where('term_id', $currentTerm->id)->count();
            $termAssignmentsCount = Assignment::where('session_id', $currentSession->id)
                ->where('term_id', $currentTerm->id)->count();
            $termNoticesCount = Notice::where('is_published', true)
                ->where('created_at', '>=', $currentTerm->start_date ?? now()->startOfYear())
                ->count();
        }

        // ── Unassigned Teachers Alert ────────────────────────────────
        $unassignedTeachers = User::where('role', 'teacher')
            ->where('is_active', true)
            ->whereDoesntHave('assignedClasses')
            ->get(['id', 'name']);

        // ── Recent Logins (staff only) ───────────────────────────────
        $recentLogins = User::whereIn('role', ['school_admin', 'teacher'])
            ->whereNotNull('last_login_at')
            ->orderByDesc('last_login_at')
            ->limit(5)
            ->get(['id', 'name', 'role', 'last_login_at', 'last_login_ip']);

        // ── Pending Approvals ────────────────────────────────────────
        $pendingApprovals = TeacherAction::with(['teacher:id,name', 'reviewer:id,name'])
            ->where('status', 'pending')
            ->latest()
            ->take(5)
            ->get();

        $pendingCount = TeacherAction::where('status', 'pending')->count();

        // ── Recent Activity ──────────────────────────────────────────
        $recentActivity = AuditLog::with(['user' => fn ($q) => $q->withoutGlobalScopes()])
            ->latest()
            ->take(10)
            ->get();

        return view('admin.dashboard', compact(
            'school', 'currentSession', 'currentTerm',
            'totalStudents', 'maleStudents', 'femaleStudents',
            'totalTeachers', 'totalParents', 'totalClasses',
            'aiFreeCredits', 'aiPurchasedCredits', 'aiTotalCredits', 'aiFreeResetDate',
            'levelBreakdown', 'classOccupancy',
            'resultsUploaded', 'resultsTotal',
            'assignmentCoverage', 'weeksPerTerm',
            'termResultsCount', 'termAssignmentsCount', 'termNoticesCount',
            'unassignedTeachers', 'recentLogins',
            'pendingApprovals', 'pendingCount',
            'recentActivity',
        ));
    }
}
