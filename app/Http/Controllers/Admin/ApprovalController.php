<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Assignment;
use App\Models\Game;
use App\Models\Notice;
use App\Models\Quiz;
use App\Models\Result;
use App\Models\TeacherAction;
use App\Notifications\SubmissionReviewed;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ApprovalController extends Controller
{
    public function index(Request $request): View
    {
        $query = TeacherAction::with(['teacher:id,name'])
            ->orderByDesc('created_at');

        $status = $request->input('status', 'pending');
        if ($status !== 'all') {
            $query->where('status', $status);
        }

        $actions = $query->paginate(10)->withQueryString();
        $pendingCount = TeacherAction::where('status', 'pending')->count();

        return view('admin.approvals.index', compact('actions', 'pendingCount', 'status'));
    }

    public function approve(TeacherAction $action): RedirectResponse
    {
        $action->update([
            'status' => 'approved',
            'reviewed_by' => auth()->id(),
            'reviewed_at' => now(),
        ]);

        // Update the related entity status
        $this->updateEntityStatus($action, 'approved');

        // Notify the teacher if they have an email
        $teacher = $action->teacher;
        if ($teacher?->email) {
            $teacher->notify(new SubmissionReviewed($action->entity_type, 'approved'));
        }

        return redirect()->route('admin.approvals.index')
            ->with('success', __(':type approved.', ['type' => ucfirst($action->entity_type)]));
    }

    public function reject(Request $request, TeacherAction $action): RedirectResponse
    {
        $validated = $request->validate([
            'rejection_reason' => ['required', 'string', 'max:500'],
        ]);

        $action->update([
            'status' => 'rejected',
            'reviewed_by' => auth()->id(),
            'reviewed_at' => now(),
            'rejection_reason' => $validated['rejection_reason'],
        ]);

        // Update the related entity status
        $this->updateEntityStatus($action, 'rejected');

        // Notify the teacher if they have an email
        $teacher = $action->teacher;
        if ($teacher?->email) {
            $teacher->notify(new SubmissionReviewed($action->entity_type, 'rejected', $validated['rejection_reason']));
        }

        return redirect()->route('admin.approvals.index')
            ->with('success', __(':type rejected.', ['type' => ucfirst($action->entity_type)]));
    }

    private function updateEntityStatus(TeacherAction $action, string $status): void
    {
        $model = match ($action->entity_type) {
            'result' => Result::find($action->entity_id),
            'assignment' => Assignment::find($action->entity_id),
            'notice' => Notice::find($action->entity_id),
            'quiz' => Quiz::find($action->entity_id),
            'game' => Game::find($action->entity_id),
            default => null,
        };

        if ($model) {
            $updateData = ['status' => $status];

            if ($status === 'approved') {
                $updateData['approved_by'] = auth()->id();
                $updateData['approved_at'] = now();

                if (in_array($action->entity_type, ['quiz', 'game'], true)) {
                    $updateData['is_published'] = true;
                    $updateData['published_at'] = now();
                }
            }

            $model->update($updateData);
        }
    }
}
