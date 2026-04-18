<?php

declare(strict_types=1);

namespace App\Http\Controllers\Parent;

use App\Http\Controllers\Controller;
use App\Models\GamePlay;
use App\Models\User;
use Illuminate\View\View;

class ChildGameStatsController extends Controller
{
    public function index(User $child): View
    {
        $parent = auth()->user();

        // Verify parent-child link
        if (! $parent->children()->where('student_id', $child->id)->exists()) {
            abort(403);
        }

        $plays = GamePlay::with(['game:id,title,game_type,class_id,difficulty', 'game.class:id,name'])
            ->where('student_id', $child->id)
            ->where('completed', true)
            ->orderByDesc('completed_at')
            ->get();

        return view('parent.games.stats', compact('child', 'plays'));
    }
}
