<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AuditLogController extends Controller
{
    public function index(Request $request): View
    {
        $query = AuditLog::with('user:id,name')
            ->orderByDesc('created_at');

        if ($request->filled('action')) {
            $query->where('action', 'like', "%{$request->input('action')}%");
        }

        if ($request->filled('user_id')) {
            $query->where('user_id', $request->input('user_id'));
        }

        $logs = $query->paginate(10)->withQueryString();

        return view('admin.audit-logs.index', compact('logs'));
    }

    public function exportCsv(Request $request): StreamedResponse
    {
        $query = AuditLog::with('user:id,name')->orderByDesc('created_at');

        if ($request->filled('action')) {
            $query->where('action', 'like', "%{$request->input('action')}%");
        }

        if ($request->filled('user_id')) {
            $query->where('user_id', $request->input('user_id'));
        }

        $logs = $query->get();
        $filename = 'audit-logs-'.now()->format('Y-m-d').'.csv';

        return response()->streamDownload(function () use ($logs) {
            $handle = fopen('php://output', 'w');
            fwrite($handle, "\xEF\xBB\xBF");
            fputcsv($handle, ['Date', 'User', 'Action', 'Entity Type', 'Entity ID', 'IP Address', 'User Agent']);
            foreach ($logs as $log) {
                fputcsv($handle, [
                    $log->created_at->format('Y-m-d H:i:s'),
                    $log->user?->name ?? 'System',
                    $log->action,
                    $log->entity_type ?? '',
                    $log->entity_id ?? '',
                    $log->ip_address ?? '',
                    $log->user_agent ?? '',
                ]);
            }
            fclose($handle);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }
}
