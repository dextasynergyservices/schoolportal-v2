<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\AcademicSession;
use App\Models\Term;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\WithSchoolContext;
use Tests\TestCase;

class AdminSessionTermTest extends TestCase
{
    use RefreshDatabase;
    use WithSchoolContext;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpSchoolContext();
    }

    // ── Sessions ──

    public function test_admin_can_view_sessions_index(): void
    {
        $this->actingAs($this->admin)
            ->get(route('admin.sessions.index'))
            ->assertOk()
            ->assertViewIs('admin.sessions.index');
    }

    public function test_admin_can_create_session(): void
    {
        $this->actingAs($this->admin)
            ->post(route('admin.sessions.store'), [
                'name' => '2026/2027',
                'start_date' => '2026-09-01',
                'end_date' => '2027-07-31',
            ])
            ->assertRedirect(route('admin.sessions.index'));

        $this->assertDatabaseHas('academic_sessions', [
            'name' => '2026/2027',
            'school_id' => $this->school->id,
        ]);

        // Should auto-create 3 terms
        $session = AcademicSession::withoutGlobalScopes()
            ->where('name', '2026/2027')
            ->where('school_id', $this->school->id)
            ->first();

        $this->assertCount(3, $session->terms);
    }

    public function test_admin_cannot_create_session_with_invalid_dates(): void
    {
        $this->actingAs($this->admin)
            ->post(route('admin.sessions.store'), [
                'name' => '2026/2027',
                'start_date' => '2027-09-01',
                'end_date' => '2026-07-31', // before start
            ])
            ->assertSessionHasErrors('end_date');
    }

    public function test_admin_can_activate_session(): void
    {
        $session = AcademicSession::withoutGlobalScopes()
            ->where('school_id', $this->school->id)->first();

        // The SchoolSetupService already creates it as active, so deactivate first
        $session->update(['is_current' => false, 'status' => 'upcoming']);

        $this->actingAs($this->admin)
            ->post(route('admin.sessions.activate', $session))
            ->assertRedirect(route('admin.sessions.index'));

        $fresh = AcademicSession::withoutGlobalScopes()->find($session->id);
        $this->assertTrue((bool) $fresh->is_current);
        $this->assertEquals('active', $fresh->status);
    }

    public function test_activating_session_deactivates_previous(): void
    {
        $session1 = AcademicSession::withoutGlobalScopes()
            ->where('school_id', $this->school->id)->first();
        $session1->update(['is_current' => true, 'status' => 'active']);

        $session2 = AcademicSession::create([
            'school_id' => $this->school->id,
            'name' => '2026/2027',
            'start_date' => '2026-09-01',
            'end_date' => '2027-07-31',
        ]);

        $this->actingAs($this->admin)
            ->post(route('admin.sessions.activate', $session2))
            ->assertRedirect();

        $this->assertFalse($session1->fresh()->is_current);
        $this->assertTrue($session2->fresh()->is_current);
    }

    public function test_admin_can_update_session(): void
    {
        $session = AcademicSession::withoutGlobalScopes()
            ->where('school_id', $this->school->id)->first();

        $this->actingAs($this->admin)
            ->put(route('admin.sessions.update', $session), [
                'name' => 'Updated Session',
                'start_date' => '2025-09-01',
                'end_date' => '2026-07-31',
            ])
            ->assertRedirect(route('admin.sessions.index'));

        $this->assertEquals('Updated Session', $session->fresh()->name);
    }

    // ── Terms ──

    public function test_admin_can_activate_term(): void
    {
        $term = Term::withoutGlobalScopes()
            ->where('school_id', $this->school->id)->first();

        $this->actingAs($this->admin)
            ->post(route('admin.terms.activate', $term))
            ->assertRedirect(route('admin.sessions.index'));

        $this->assertTrue($term->fresh()->is_current);
        $this->assertEquals('active', $term->fresh()->status);
    }

    public function test_admin_can_update_term(): void
    {
        $term = Term::withoutGlobalScopes()
            ->where('school_id', $this->school->id)->first();

        $this->actingAs($this->admin)
            ->put(route('admin.terms.update', $term), [
                'name' => 'Updated Term',
                'start_date' => '2025-09-01',
                'end_date' => '2025-12-15',
            ])
            ->assertRedirect(route('admin.sessions.index'));

        $this->assertEquals('Updated Term', $term->fresh()->name);
    }

    public function test_student_cannot_access_session_routes(): void
    {
        $student = $this->createSchoolUser('student');

        $this->actingAs($student)
            ->get(route('admin.sessions.index'))
            ->assertForbidden();
    }
}
