<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Game;
use App\Models\SchoolClass;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class GameController extends Controller
{
    public function index(Request $request): View
    {
        $query = Game::with(['class:id,name', 'creator:id,name', 'session:id,name', 'term:id,name']);

        if ($request->filled('class_id')) {
            $query->where('class_id', $request->input('class_id'));
        }

        if ($request->filled('game_type')) {
            $query->where('game_type', $request->input('game_type'));
        }

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        $games = $query->orderByDesc('created_at')->paginate(20)->withQueryString();
        $classes = SchoolClass::where('is_active', true)->orderBy('name')->get();

        return view('admin.games.index', compact('games', 'classes'));
    }

    public function show(Game $game): View
    {
        $game->load(['class:id,name', 'creator:id,name', 'session:id,name', 'term:id,name']);

        return view('admin.games.show', compact('game'));
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

        $plays = $game->plays()
            ->with('student:id,name,username')
            ->where('completed', true)
            ->orderByDesc('percentage')
            ->get();

        $stats = [
            'total_plays' => $plays->count(),
            'unique_players' => $plays->pluck('student_id')->unique()->count(),
            'average_score' => $plays->avg('percentage') ? round($plays->avg('percentage'), 1) : 0,
            'highest_score' => $plays->max('percentage') ?? 0,
        ];

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
