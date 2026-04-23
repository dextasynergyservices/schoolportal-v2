<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Game;
use App\Models\SchoolClass;
use App\Models\TeacherAction;
use App\Services\NotificationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class GameController extends Controller
{
    public function index(Request $request): View
    {
        $query = Game::with(['class:id,name', 'creator:id,name', 'session:id,name', 'term:id,name', 'latestTeacherAction']);

        if ($request->filled('class_id')) {
            $query->where('class_id', $request->input('class_id'));
        }

        if ($request->filled('game_type')) {
            $query->where('game_type', $request->input('game_type'));
        }

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        $games = $query->orderByDesc('created_at')->paginate(10)->withQueryString();
        $classes = SchoolClass::where('is_active', true)->orderBy('name')->get();

        return view('admin.games.index', compact('games', 'classes'));
    }

    public function show(Game $game): View
    {
        $game->load(['class:id,name', 'creator:id,name', 'session:id,name', 'term:id,name']);

        $teacherAction = TeacherAction::where('entity_type', 'game')
            ->where('entity_id', $game->id)
            ->latest()
            ->first();

        return view('admin.games.show', compact('game', 'teacherAction'));
    }

    public function publish(Game $game): RedirectResponse
    {
        if ($game->status !== 'approved') {
            return redirect()->back()->with('error', __('Only approved games can be published.'));
        }

        $game->update([
            'is_published' => true,
            'published_at' => now(),
        ]);

        app(NotificationService::class)->notifyGamePublished($game);

        return redirect()->route('admin.games.index')
            ->with('success', __('Game published and visible to students.'));
    }

    public function unpublish(Game $game): RedirectResponse
    {
        $game->update([
            'is_published' => false,
            'published_at' => null,
        ]);

        return redirect()->route('admin.games.index')
            ->with('success', __('Game unpublished.'));
    }

    public function stats(Game $game): View
    {
        $game->load('class:id,name');

        $baseQuery = $game->plays()->where('completed', true);

        $stats = [
            'total_plays' => (clone $baseQuery)->count(),
            'unique_players' => (clone $baseQuery)->distinct('student_id')->count('student_id'),
            'average_score' => round((float) (clone $baseQuery)->avg('percentage'), 1),
            'highest_score' => (float) ((clone $baseQuery)->max('percentage') ?? 0),
        ];

        $plays = $baseQuery
            ->with('student:id,name,username')
            ->orderByDesc('percentage')
            ->paginate(10);

        return view('admin.games.stats', compact('game', 'plays', 'stats'));
    }

    public function destroy(Game $game): RedirectResponse
    {
        if ($game->is_published) {
            return redirect()->back()->with('error', __('Published games cannot be deleted.'));
        }

        $game->delete();

        return redirect()->route('admin.games.index')
            ->with('success', __('Game deleted.'));
    }
}
