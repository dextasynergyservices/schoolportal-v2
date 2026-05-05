<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AcademicSession;
use App\Models\Assignment;
use App\Models\Result;
use App\Models\SchoolClass;
use App\Models\SchoolLevel;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\View\View;

class AnalyticsController extends Controller
{
    public function __invoke(Request $request): View
    {
        $school = app('current.school');

        // ── Session/Term filtering ───────────────────────────────────
        // These stay outside the cache — they are request-driven and fast.
        $allSessions = AcademicSession::orderByDesc('start_date')->get(['id', 'name', 'is_current']);

        if ($request->filled('session_id')) {
            $currentSession = AcademicSession::find((int) $request->input('session_id'));
        } else {
            $currentSession = $school->currentSession();
        }

        if ($currentSession && $request->filled('term_id')) {
            $currentTerm = $currentSession->terms()->find((int) $request->input('term_id'));
        } elseif ($currentSession) {
            $currentTerm = $request->filled('session_id')
                ? $currentSession->terms()->first()
                : $school->currentTerm();
        } else {
            $currentTerm = null;
        }

        $sessionTerms = $currentSession?->terms()->orderBy('term_number')->get(['id', 'name', 'term_number', 'is_current']) ?? collect();

        // ── Aggregated Analytics (cached per school/session/term) ────
        // A version counter is incremented by model observers whenever school
        // data changes (results, assignments, users, classes). This busts all
        // analytics caches for the school without needing Redis tag support.
        $sessionId = $currentSession?->id ?? 0;
        $termId = $currentTerm?->id ?? 0;
        $version = (int) Cache::get("school:{$school->id}:analytics:version", 0);
        $cacheKey = "school:{$school->id}:analytics:v{$version}:{$sessionId}:{$termId}";

        $data = Cache::remember($cacheKey, now()->addMinutes(15), function () use ($school, $currentSession, $currentTerm) {
            // ── Students by Level ────────────────────────────────────
            $studentsByLevel = User::where('role', 'student')
                ->where('is_active', true)
                ->whereNotNull('level_id')
                ->selectRaw('level_id, count(*) as total')
                ->groupBy('level_id')
                ->pluck('total', 'level_id');

            $totalStudents = User::where('role', 'student')->count();

            $levelBreakdown = SchoolLevel::where('is_active', true)
                ->orderBy('sort_order')
                ->get(['id', 'name'])
                ->map(fn ($level) => [
                    'name' => $level->name,
                    'count' => $studentsByLevel[$level->id] ?? 0,
                ])->values()->all();

            // ── Class Occupancy ──────────────────────────────────────
            $classOccupancy = SchoolClass::where('is_active', true)
                ->whereNotNull('capacity')
                ->where('capacity', '>', 0)
                ->withCount('students')
                ->with('level:id,name')
                ->orderBy('sort_order')
                ->get(['id', 'name', 'capacity', 'level_id'])
                ->map(fn ($c) => [
                    'name' => $c->name,
                    'capacity' => $c->capacity,
                    'students_count' => $c->students_count,
                    'level_name' => $c->level?->name,
                ])->values()->all();

            // ── Results Upload Progress ──────────────────────────────
            $resultsUploaded = 0;
            $resultsTotal = $totalStudents;
            if ($currentSession && $currentTerm) {
                $resultsUploaded = Result::where('session_id', $currentSession->id)
                    ->where('term_id', $currentTerm->id)
                    ->where('status', 'approved')
                    ->count();
            }

            // ── Assignments Coverage by Class ────────────────────────
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
                ])->values()->all();
            }

            // ── Gender Breakdown ─────────────────────────────────────
            $maleStudents = User::where('role', 'student')->where('gender', 'male')->count();
            $femaleStudents = User::where('role', 'student')->where('gender', 'female')->count();

            return compact(
                'totalStudents', 'maleStudents', 'femaleStudents',
                'levelBreakdown', 'classOccupancy',
                'resultsUploaded', 'resultsTotal',
                'assignmentCoverage', 'weeksPerTerm',
            );
        });

        // Unpack cached data into named variables for the view.
        [
            'totalStudents' => $totalStudents,
            'maleStudents' => $maleStudents,
            'femaleStudents' => $femaleStudents,
            'levelBreakdown' => $levelBreakdown,
            'classOccupancy' => $classOccupancy,
            'resultsUploaded' => $resultsUploaded,
            'resultsTotal' => $resultsTotal,
            'assignmentCoverage' => $assignmentCoverage,
            'weeksPerTerm' => $weeksPerTerm,
        ] = $data;

        // Re-wrap as Collections so the view can call ->isNotEmpty(), ->count(), ->sum() etc.
        // Values are stored as plain PHP arrays in cache to avoid serialization issues.
        $levelBreakdown = collect($levelBreakdown);
        $classOccupancy = collect($classOccupancy);
        $assignmentCoverage = collect($assignmentCoverage);

        // ── Recent Staff Logins (short TTL — changes on every login) ─
        $recentLogins = collect(Cache::remember(
            "school:{$school->id}:analytics:logins",
            now()->addMinutes(2),
            fn () => User::whereIn('role', ['school_admin', 'teacher'])
                ->whereNotNull('last_login_at')
                ->orderByDesc('last_login_at')
                ->limit(20)
                ->get(['id', 'name', 'role', 'last_login_at', 'last_login_ip', 'avatar_url'])
                ->map(fn ($u) => [
                    'name' => $u->name,
                    'role' => $u->role,
                    'avatar_url' => $u->avatar_url,
                    'avatar_thumb_url' => $u->avatarTableUrl(),
                    'last_login_at' => $u->last_login_at?->toIso8601String(),
                    'last_login_ip' => $u->last_login_ip,
                ])->values()->all()
        ));

        $tab = $request->query('tab', 'overview');

        return view('admin.analytics', compact(
            'school', 'currentSession', 'currentTerm',
            'allSessions', 'sessionTerms',
            'totalStudents', 'maleStudents', 'femaleStudents',
            'levelBreakdown',
            'classOccupancy',
            'resultsUploaded', 'resultsTotal',
            'assignmentCoverage', 'weeksPerTerm',
            'recentLogins',
            'tab',
        ));
    }
}
