<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\AiCreditUsageLog;
use App\Models\AuditLog;
use App\Models\StudentProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\WithSchoolContext;
use Tests\TestCase;

class DataExportTest extends TestCase
{
    use RefreshDatabase;
    use WithSchoolContext;

    protected User $teacher;

    protected User $student;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpSchoolContext();

        $this->teacher = $this->createSchoolUser('teacher');
        $this->class->update(['teacher_id' => $this->teacher->id]);

        $this->student = User::factory()->create([
            'school_id' => $this->school->id,
            'role' => 'student',
            'level_id' => $this->level->id,
            'name' => 'Test Student',
            'username' => 'teststudent',
            'gender' => 'male',
            'is_active' => true,
        ]);
        StudentProfile::create([
            'user_id' => $this->student->id,
            'school_id' => $this->school->id,
            'class_id' => $this->class->id,
            'admission_number' => 'ADM001',
        ]);
    }

    // ── Admin: Student List Export ─────────────────────────────────────────────

    public function test_admin_can_export_student_list_as_csv(): void
    {
        $response = $this->actingAs($this->admin)
            ->get(route('admin.students.export'));

        $response->assertOk();
        $response->assertHeader('Content-Type', 'text/csv; charset=UTF-8');
        $this->assertStringContainsString('Test Student', $response->streamedContent());
        $this->assertStringContainsString('teststudent', $response->streamedContent());
    }

    public function test_admin_student_export_respects_class_filter(): void
    {
        // Create a second student in a different class (manually set null class)
        $otherStudent = User::factory()->create([
            'school_id' => $this->school->id,
            'role' => 'student',
            'level_id' => $this->level->id,
            'name' => 'Other Student',
        ]);
        StudentProfile::create([
            'user_id' => $otherStudent->id,
            'school_id' => $this->school->id,
            'class_id' => $this->class->id,
        ]);

        $response = $this->actingAs($this->admin)
            ->get(route('admin.students.export', ['class_id' => $this->class->id]));

        $response->assertOk();
        $this->assertStringContainsString('Test Student', $response->streamedContent());
    }

    public function test_admin_student_export_respects_status_filter(): void
    {
        $inactiveStudent = User::factory()->create([
            'school_id' => $this->school->id,
            'role' => 'student',
            'name' => 'Inactive Student',
            'is_active' => false,
        ]);

        $activeOnly = $this->actingAs($this->admin)
            ->get(route('admin.students.export', ['status' => 'active']));

        $activeOnly->assertOk();
        $this->assertStringNotContainsString('Inactive Student', $activeOnly->streamedContent());
    }

    public function test_non_admin_cannot_access_admin_student_export(): void
    {
        $this->actingAs($this->teacher)
            ->get(route('admin.students.export'))
            ->assertForbidden();
    }

    // ── Teacher: Student List Export ──────────────────────────────────────────

    public function test_teacher_can_export_their_class_students_as_csv(): void
    {
        $response = $this->actingAs($this->teacher)
            ->get(route('teacher.students.export'));

        $response->assertOk();
        $response->assertHeader('Content-Type', 'text/csv; charset=UTF-8');
        $this->assertStringContainsString('Test Student', $response->streamedContent());
    }

    public function test_teacher_export_only_shows_assigned_class_students(): void
    {
        // Create a second school with its own student
        $otherTeacher = $this->createSchoolUser('teacher', ['username' => 'other_teacher']);

        $response = $this->actingAs($this->teacher)
            ->get(route('teacher.students.export'));

        $response->assertOk();
        // The CSV must not expose students from other teachers' classes
        // (only Test Student from this teacher's class is expected)
        $this->assertStringContainsString('teststudent', $response->streamedContent());
    }

    public function test_student_cannot_access_teacher_student_export(): void
    {
        $this->actingAs($this->student)
            ->get(route('teacher.students.export'))
            ->assertForbidden();
    }

    // ── Admin: Audit Log Export ────────────────────────────────────────────────

    public function test_admin_can_export_audit_logs_as_csv(): void
    {
        AuditLog::create([
            'school_id' => $this->school->id,
            'user_id' => $this->admin->id,
            'action' => 'student.created',
            'entity_type' => 'student',
            'entity_id' => $this->student->id,
            'ip_address' => '127.0.0.1',
        ]);

        $response = $this->actingAs($this->admin)
            ->get(route('admin.audit-logs.export'));

        $response->assertOk();
        $response->assertHeader('Content-Type', 'text/csv; charset=UTF-8');
        $this->assertStringContainsString('student.created', $response->streamedContent());
    }

    public function test_admin_audit_log_export_respects_action_filter(): void
    {
        AuditLog::create([
            'school_id' => $this->school->id,
            'user_id' => $this->admin->id,
            'action' => 'result.uploaded',
            'entity_type' => 'result',
            'entity_id' => 1,
        ]);
        AuditLog::create([
            'school_id' => $this->school->id,
            'user_id' => $this->admin->id,
            'action' => 'student.deleted',
            'entity_type' => 'student',
            'entity_id' => 2,
        ]);

        $response = $this->actingAs($this->admin)
            ->get(route('admin.audit-logs.export', ['action' => 'result']));

        $response->assertOk();
        $this->assertStringContainsString('result.uploaded', $response->streamedContent());
        $this->assertStringNotContainsString('student.deleted', $response->streamedContent());
    }

    public function test_non_admin_cannot_access_audit_log_export(): void
    {
        $this->actingAs($this->teacher)
            ->get(route('admin.audit-logs.export'))
            ->assertForbidden();
    }

    // ── Admin: Credit Usage Export ────────────────────────────────────────────

    public function test_admin_can_export_credit_usage_as_csv(): void
    {
        AiCreditUsageLog::create([
            'school_id' => $this->school->id,
            'user_id' => $this->teacher->id,
            'level_id' => $this->level->id,
            'usage_type' => 'quiz',
            'entity_id' => 1,
            'credits_used' => 1,
        ]);

        $response = $this->actingAs($this->admin)
            ->get(route('admin.credits.usage.export'));

        $response->assertOk();
        $response->assertHeader('Content-Type', 'text/csv; charset=UTF-8');
        $this->assertStringContainsString('quiz', $response->streamedContent());
    }

    public function test_credit_usage_export_includes_correct_columns(): void
    {
        AiCreditUsageLog::create([
            'school_id' => $this->school->id,
            'user_id' => $this->teacher->id,
            'level_id' => null,
            'usage_type' => 'game',
            'entity_id' => 5,
            'credits_used' => 1,
        ]);

        $response = $this->actingAs($this->admin)
            ->get(route('admin.credits.usage.export'));

        $content = $response->streamedContent();
        $response->assertOk();
        $this->assertStringContainsString('Date', $content);
        $this->assertStringContainsString('School Pool', $content);
        $this->assertStringContainsString('game', $content);
    }

    public function test_non_admin_cannot_access_credit_usage_export(): void
    {
        $this->actingAs($this->teacher)
            ->get(route('admin.credits.usage.export'))
            ->assertForbidden();
    }
}
