<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Services\AiCreditService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\WithSchoolContext;
use Tests\TestCase;

class AiCreditTest extends TestCase
{
    use RefreshDatabase;
    use WithSchoolContext;

    protected AiCreditService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpSchoolContext();
        $this->service = app(AiCreditService::class);
    }

    public function test_school_balance_returns_sum_of_free_and_purchased_credits(): void
    {
        $this->school->ai_free_credits = 8;
        $this->school->ai_purchased_credits = 22;
        $this->school->save();

        $balance = $this->service->getSchoolBalance($this->school);

        $this->assertSame(30, $balance);
    }

    public function test_has_credits_returns_true_when_credits_available(): void
    {
        $this->school->ai_free_credits = 5;
        $this->school->save();

        $this->assertTrue($this->service->hasCredits($this->school));
    }

    public function test_has_credits_returns_false_when_no_credits(): void
    {
        $this->school->ai_free_credits = 0;
        $this->school->ai_purchased_credits = 0;
        $this->school->save();

        $this->assertFalse($this->service->hasCredits($this->school));
    }

    public function test_deduct_credit_decrements_free_credits_first(): void
    {
        $this->school->ai_free_credits = 5;
        $this->school->ai_purchased_credits = 10;
        $this->school->save();

        $result = $this->service->deductCredit(
            school: $this->school,
            user: $this->admin,
            usageType: 'quiz',
        );

        $this->assertTrue($result);
        $this->school->refresh();
        $this->assertSame(4, $this->school->ai_free_credits);
        $this->assertSame(10, $this->school->ai_purchased_credits);
    }

    public function test_deduct_credit_uses_purchased_when_free_exhausted(): void
    {
        $this->school->ai_free_credits = 0;
        $this->school->ai_purchased_credits = 10;
        $this->school->save();

        $result = $this->service->deductCredit(
            school: $this->school,
            user: $this->admin,
            usageType: 'game',
        );

        $this->assertTrue($result);
        $this->school->refresh();
        $this->assertSame(0, $this->school->ai_free_credits);
        $this->assertSame(9, $this->school->ai_purchased_credits);
    }

    public function test_deduct_credit_returns_false_when_no_credits_available(): void
    {
        $this->school->ai_free_credits = 0;
        $this->school->ai_purchased_credits = 0;
        $this->school->save();

        $result = $this->service->deductCredit(
            school: $this->school,
            user: $this->admin,
            usageType: 'quiz',
        );

        $this->assertFalse($result);
        $this->assertDatabaseCount('ai_credit_usage_log', 0);
    }

    public function test_deduct_credit_logs_usage_to_database(): void
    {
        $this->school->ai_free_credits = 3;
        $this->school->save();

        $this->service->deductCredit(
            school: $this->school,
            user: $this->admin,
            usageType: 'quiz',
            entityId: 42,
        );

        $this->assertDatabaseHas('ai_credit_usage_log', [
            'school_id' => $this->school->id,
            'user_id' => $this->admin->id,
            'usage_type' => 'quiz',
            'entity_id' => 42,
            'credits_used' => 1,
        ]);
    }

    public function test_admin_can_view_credits_index_page(): void
    {
        $this->actingAs($this->admin)
            ->get(route('admin.credits.index'))
            ->assertOk();
    }

    public function test_non_admin_cannot_view_credits_page(): void
    {
        $teacher = $this->createSchoolUser('teacher');

        $this->actingAs($teacher)
            ->get(route('admin.credits.index'))
            ->assertForbidden();
    }

    public function test_purchase_credits_must_be_a_multiple_of_five(): void
    {
        $response = $this->actingAs($this->admin)
            ->post(route('admin.credits.purchase.process'), [
                'credits' => 7,
            ]);

        // Controller redirects back with an 'error' session key
        $response->assertRedirect();
        $response->assertSessionHas('error');

        $this->assertDatabaseCount('ai_credit_purchases', 0);
    }
}
