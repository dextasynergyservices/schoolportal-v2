<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\TeacherAction;
use App\Models\User;
use App\Notifications\SubmissionApprovedNotification;
use App\Notifications\SubmissionRejectedNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\Concerns\WithSchoolContext;
use Tests\TestCase;

class BulkApprovalTest extends TestCase
{
    use RefreshDatabase;
    use WithSchoolContext;

    protected User $teacher;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpSchoolContext();
        $this->teacher = $this->createSchoolUser('teacher');
        $this->class->update(['teacher_id' => $this->teacher->id]);
    }

    private function makeTeacherAction(string $entityType = 'result', string $status = 'pending'): TeacherAction
    {
        return TeacherAction::create([
            'school_id' => $this->school->id,
            'teacher_id' => $this->teacher->id,
            'action_type' => 'upload_result',
            'entity_type' => $entityType,
            'entity_id' => 9999, // Non-existent entity — controller handles null gracefully
            'status' => $status,
        ]);
    }

    public function test_admin_can_bulk_approve_pending_actions(): void
    {
        Notification::fake();

        $action1 = $this->makeTeacherAction();
        $action2 = $this->makeTeacherAction();

        $this->actingAs($this->admin)
            ->post(route('admin.approvals.bulk-approve'), [
                'action_ids' => [$action1->id, $action2->id],
            ])
            ->assertRedirect(route('admin.approvals.index'))
            ->assertSessionHas('success');

        $this->assertDatabaseHas('teacher_actions', ['id' => $action1->id, 'status' => 'approved']);
        $this->assertDatabaseHas('teacher_actions', ['id' => $action2->id, 'status' => 'approved']);

        Notification::assertSentTo($this->teacher, SubmissionApprovedNotification::class);
    }

    public function test_bulk_approve_skips_already_approved_actions(): void
    {
        $pending = $this->makeTeacherAction('result', 'pending');
        $approved = $this->makeTeacherAction('result', 'approved');

        $this->actingAs($this->admin)
            ->post(route('admin.approvals.bulk-approve'), [
                'action_ids' => [$pending->id, $approved->id],
            ])
            ->assertRedirect(route('admin.approvals.index'));

        // Only the pending one should change
        $this->assertDatabaseHas('teacher_actions', ['id' => $pending->id, 'status' => 'approved', 'reviewed_by' => $this->admin->id]);
        // Already approved — reviewed_by remains null (was never updated)
        $this->assertDatabaseHas('teacher_actions', ['id' => $approved->id, 'reviewed_by' => null]);
    }

    public function test_bulk_approve_returns_error_when_no_pending_found(): void
    {
        $approved = $this->makeTeacherAction('result', 'approved');

        $this->actingAs($this->admin)
            ->post(route('admin.approvals.bulk-approve'), [
                'action_ids' => [$approved->id],
            ])
            ->assertRedirect(route('admin.approvals.index'))
            ->assertSessionHas('error');
    }

    public function test_bulk_approve_validates_max_50_ids(): void
    {
        $this->actingAs($this->admin)
            ->post(route('admin.approvals.bulk-approve'), [
                'action_ids' => range(1, 51),
            ])
            ->assertSessionHasErrors('action_ids');
    }

    public function test_bulk_approve_requires_action_ids(): void
    {
        $this->actingAs($this->admin)
            ->post(route('admin.approvals.bulk-approve'), [])
            ->assertSessionHasErrors('action_ids');
    }

    public function test_admin_can_bulk_reject_pending_actions(): void
    {
        Notification::fake();

        $action1 = $this->makeTeacherAction();
        $action2 = $this->makeTeacherAction();

        $this->actingAs($this->admin)
            ->post(route('admin.approvals.bulk-reject'), [
                'action_ids' => [$action1->id, $action2->id],
                'rejection_reason' => 'Files are incomplete.',
            ])
            ->assertRedirect(route('admin.approvals.index'))
            ->assertSessionHas('success');

        $this->assertDatabaseHas('teacher_actions', [
            'id' => $action1->id,
            'status' => 'rejected',
            'rejection_reason' => 'Files are incomplete.',
        ]);
        $this->assertDatabaseHas('teacher_actions', [
            'id' => $action2->id,
            'status' => 'rejected',
            'rejection_reason' => 'Files are incomplete.',
        ]);

        Notification::assertSentTo($this->teacher, SubmissionRejectedNotification::class);
    }

    public function test_bulk_reject_requires_rejection_reason(): void
    {
        $action = $this->makeTeacherAction();

        $this->actingAs($this->admin)
            ->post(route('admin.approvals.bulk-reject'), [
                'action_ids' => [$action->id],
            ])
            ->assertSessionHasErrors('rejection_reason');
    }

    public function test_bulk_reject_reason_cannot_exceed_500_chars(): void
    {
        $action = $this->makeTeacherAction();

        $this->actingAs($this->admin)
            ->post(route('admin.approvals.bulk-reject'), [
                'action_ids' => [$action->id],
                'rejection_reason' => str_repeat('a', 501),
            ])
            ->assertSessionHasErrors('rejection_reason');
    }

    public function test_teacher_cannot_bulk_approve(): void
    {
        $action = $this->makeTeacherAction();

        $this->actingAs($this->teacher)
            ->post(route('admin.approvals.bulk-approve'), [
                'action_ids' => [$action->id],
            ])
            ->assertForbidden();
    }

    public function test_teacher_cannot_bulk_reject(): void
    {
        $action = $this->makeTeacherAction();

        $this->actingAs($this->teacher)
            ->post(route('admin.approvals.bulk-reject'), [
                'action_ids' => [$action->id],
                'rejection_reason' => 'Test reason.',
            ])
            ->assertForbidden();
    }

    public function test_bulk_approve_ids_must_exist_in_teacher_actions(): void
    {
        $this->actingAs($this->admin)
            ->post(route('admin.approvals.bulk-approve'), [
                'action_ids' => [99999],
            ])
            ->assertSessionHasErrors('action_ids.0');
    }
}
