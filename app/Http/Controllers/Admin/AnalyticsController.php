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
use Illuminate\View\View;

class AnalyticsController extends Controller
{
    public function __invoke(Request $request): View
    {
        $school = app('current.school');

        // ── Session/Term filtering ───────────────────────────────────
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

        // ── Students by Level ────────────────────────────────────────
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
            ]);

        // ── Class Occupancy ──────────────────────────────────────────
        $classOccupancy = SchoolClass::where('is_active', true)
            ->whereNotNull('capacity')
            ->where('capacity', '>', 0)
            ->withCount('students')
            ->with('level:id,name')
            ->orderBy('sort_order')
            ->get(['id', 'name', 'capacity', 'level_id']);

        // ── Results Upload Progress ──────────────────────────────────
        $resultsUploaded = 0;
        $resultsTotal = $totalStudents;
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

        // ── Recent Staff Logins ──────────────────────────────────────
        $recentLogins = User::whereIn('role', ['school_admin', 'teacher'])
            ->whereNotNull('last_login_at')
            ->orderByDesc('last_login_at')
            ->limit(20)
            ->get(['id', 'name', 'role', 'last_login_at', 'last_login_ip', 'avatar_url']);

        // ── Gender Breakdown ─────────────────────────────────────────
        $maleStudents = User::where('role', 'student')->where('gender', 'male')->count();
        $femaleStudents = User::where('role', 'student')->where('gender', 'female')->count();

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
