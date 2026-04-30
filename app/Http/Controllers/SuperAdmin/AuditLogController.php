<?php

declare(strict_types=1);

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\School;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\View\View;

class AuditLogController extends Controller
{
    private const PER_PAGE = 30;

    private const EXPORT_LIMIT = 5000;

    /** @var array<string, string> Human-readable action category labels */
    private const ACTION_CATEGORIES = [
        'all' => 'All Actions',
        'created' => 'Created',
        'updated' => 'Updated',
        'deleted' => 'Deleted',
        'login' => 'Login / Logout',
        'credit' => 'AI Credits',
        'approved' => 'Approved',
        'rejected' => 'Rejected',
        'promoted' => 'Promotions',
        'password' => 'Passwords',
    ];

    public function index(Request $request): View
    {
        $query = AuditLog::withoutGlobalScopes()
            ->with([
                'user:id,name,role',
                'school:id,name,slug',
            ])
            ->orderByDesc('created_at');

        // --- Filters ---
        if ($schoolId = $request->input('school_id')) {
            $query->where('school_id', (int) $schoolId);
        }

        if ($category = $request->input('category')) {
            match ($category) {
                'all' => null,
                'login' => $query->where(function ($q) {
                    $q->where('action', 'like', 'login%')
                        ->orWhere('action', 'like', 'logout%');
                }),
                default => $query->where('action', 'like', "%{$category}%"),
            };
        }

        if ($action = trim((string) $request->input('action', ''))) {
            $query->where('action', 'like', "%{$action}%");
        }

        if ($user = trim((string) $request->input('user', ''))) {
            $query->whereHas('user', fn ($q) => $q->where('name', 'like', "%{$user}%"));
        }

        if ($from = $request->input('from')) {
            $query->whereDate('created_at', '>=', $from);
        }

        if ($to = $request->input('to')) {
            $query->whereDate('created_at', '<=', $to);
        }

        $logs = $query->paginate(self::PER_PAGE)->withQueryString();
        $total = AuditLog::withoutGlobalScopes()->count();

        $schools = School::tenants()
            ->orderBy('name')
            ->get(['id', 'name']);

        return view('super-admin.audit-logs.index', compact('logs', 'schools', 'total'));
    }

    public function export(Request $request): Response
    {
        $query = AuditLog::withoutGlobalScopes()
            ->with(['user:id,name', 'school:id,name'])
            ->orderByDesc('created_at');

        if ($schoolId = $request->input('school_id')) {
            $query->where('school_id', (int) $schoolId);
        }

        if ($category = $request->input('category')) {
            match ($category) {
                'all' => null,
                'login' => $query->where(function ($q) {
                    $q->where('action', 'like', 'login%')
                        ->orWhere('action', 'like', 'logout%');
                }),
                default => $query->where('action', 'like', "%{$category}%"),
            };
        }

        if ($action = trim((string) $request->input('action', ''))) {
            $query->where('action', 'like', "%{$action}%");
        }

        if ($user = trim((string) $request->input('user', ''))) {
            $query->whereHas('user', fn ($q) => $q->where('name', 'like', "%{$user}%"));
        }

        if ($from = $request->input('from')) {
            $query->whereDate('created_at', '>=', $from);
        }

        if ($to = $request->input('to')) {
            $query->whereDate('created_at', '<=', $to);
        }

        $logs = $query->limit(self::EXPORT_LIMIT)->get();

        $rows = [['Date', 'School', 'User', 'Role', 'Action', 'Entity Type', 'Entity ID', 'IP Address', 'Changes']];

        foreach ($logs as $log) {
            $changes = '';
            if ($log->old_values || $log->new_values) {
                $changes = json_encode(['before' => $log->old_values, 'after' => $log->new_values]);
            }

            $rows[] = [
                $log->created_at->format('Y-m-d H:i:s'),
                $log->school?->name ?? '',
                $log->user?->name ?? 'System',
                $log->user?->role ?? '',
                $log->action,
                $log->entity_type ?? '',
                $log->entity_id ?? '',
                $log->ip_address ?? '',
                $changes,
            ];
        }

        $csv = '';
        foreach ($rows as $row) {
            $csv .= implode(',', array_map(
                fn ($v) => '"'.str_replace('"', '""', (string) $v).'"',
                $row,
            ))."\r\n";
        }

        return response($csv, 200, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="audit-logs-'.now()->format('Y-m-d').'.csv"',
            'Cache-Control' => 'no-store, no-cache',
        ]);
    }

    /** Map an action string to a Flux badge colour. */
    public static function actionColor(string $action): string
    {
        if (str_contains($action, 'deleted') || str_contains($action, 'removed')) {
            return 'red';
        }

        if (str_contains($action, 'created') || str_contains($action, 'registered')) {
            return 'green';
        }

        if (str_contains($action, 'updated') || str_contains($action, 'changed') || str_contains($action, 'adjusted')) {
            return 'blue';
        }

        if (str_contains($action, 'login') || str_contains($action, 'logout')) {
            return 'violet';
        }

        if (str_contains($action, 'approved')) {
            return 'lime';
        }

        if (str_contains($action, 'rejected')) {
            return 'orange';
        }

        if (str_contains($action, 'credit') || str_contains($action, 'purchase')) {
            return 'amber';
        }

        if (str_contains($action, 'promoted') || str_contains($action, 'promotion')) {
            return 'cyan';
        }

        if (str_contains($action, 'password')) {
            return 'fuchsia';
        }

        return 'zinc';
    }
}
