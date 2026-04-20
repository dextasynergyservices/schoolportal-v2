<?php

declare(strict_types=1);

namespace App\Http\Controllers\Parent;

use App\Http\Controllers\Controller;
use App\Models\Notice;
use Illuminate\View\View;

class NoticeController extends Controller
{
    public function index(): View
    {
        $notices = Notice::where('is_published', true)
            ->where(function ($q) {
                $q->whereNull('expires_at')
                    ->orWhere('expires_at', '>=', now());
            })
            ->where(function ($q) {
                $q->whereNull('target_roles')
                    ->orWhereJsonContains('target_roles', 'parent');
            })
            ->with('creator:id,name')
            ->latest('published_at')
            ->paginate(10);

        return view('parent.notices.index', compact('notices'));
    }

    public function show(Notice $notice): View
    {
        abort_unless(
            $notice->is_published
            && ($notice->expires_at === null || $notice->expires_at->gte(now())),
            404
        );

        $notice->load('creator:id,name');

        return view('parent.notices.show', compact('notice'));
    }
}
