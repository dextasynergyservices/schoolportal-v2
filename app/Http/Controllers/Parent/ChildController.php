<?php

declare(strict_types=1);

namespace App\Http\Controllers\Parent;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\View\View;

class ChildController extends Controller
{
    public function show(User $child): View
    {
        $parent = auth()->user();

        // Ensure the child is linked to this parent
        $this->authorizeChild($parent, $child);

        $child->load([
            'studentProfile.class.teacher:id,name',
            'studentProfile.class.level:id,name',
            'studentProfile.enrolledSession:id,name',
        ]);

        return view('parent.children.show', compact('child'));
    }

    private function authorizeChild(User $parent, User $child): void
    {
        $isLinked = $parent->children()
            ->where('student_id', $child->id)
            ->exists();

        abort_unless($isLinked && $child->school_id === $parent->school_id, 403);
    }
}
