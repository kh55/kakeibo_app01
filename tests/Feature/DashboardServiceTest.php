<?php

namespace Tests\Feature;

use App\Models\Transaction;
use App\Models\User;
use App\Services\DashboardService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardServiceTest extends TestCase
{
    use RefreshDatabase;

    private DashboardService $service;
    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new DashboardService();
        $this->user = User::factory()->create();
    }

    public function test_getMonthlySummary_returns_savings_rate_as_percentage(): void
    {
        Transaction::factory()->create([
            'user_id' => $this->user->id,
            'type' => 'income',
            'amount' => 200000,
            'date' => '2025-04-15',
        ]);
        Transaction::factory()->create([
            'user_id' => $this->user->id,
            'type' => 'expense',
            'amount' => 150000,
            'date' => '2025-04-20',
        ]);

        $summary = $this->service->getMonthlySummary($this->user, 2025, 4);

        $this->assertSame(25, $summary['savings_rate']);
    }

    public function test_getMonthlySummary_returns_null_savings_rate_when_income_is_zero(): void
    {
        $summary = $this->service->getMonthlySummary($this->user, 2025, 4);

        $this->assertNull($summary['savings_rate']);
    }

    public function test_getMonthlySummary_returns_negative_savings_rate_when_expense_exceeds_income(): void
    {
        Transaction::factory()->create([
            'user_id' => $this->user->id,
            'type' => 'income',
            'amount' => 100000,
            'date' => '2025-04-15',
        ]);
        Transaction::factory()->create([
            'user_id' => $this->user->id,
            'type' => 'expense',
            'amount' => 120000,
            'date' => '2025-04-20',
        ]);

        $summary = $this->service->getMonthlySummary($this->user, 2025, 4);

        $this->assertSame(-20, $summary['savings_rate']);
    }
}
