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
        $this->service = new DashboardService;
        $this->user = User::factory()->create();
    }

    public function test_get_monthly_summary_returns_savings_rate_as_percentage(): void
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

    public function test_get_monthly_summary_returns_null_savings_rate_when_income_is_zero(): void
    {
        $summary = $this->service->getMonthlySummary($this->user, 2025, 4);

        $this->assertNull($summary['savings_rate']);
    }

    public function test_get_monthly_summary_returns_negative_savings_rate_when_expense_exceeds_income(): void
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

    public function test_get_monthly_trend_returns_six_months_of_income_and_expense(): void
    {
        // 2025年4月の収支
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
            'date' => '2025-04-10',
        ]);
        // 2025年2月の収支
        Transaction::factory()->create([
            'user_id' => $this->user->id,
            'type' => 'income',
            'amount' => 180000,
            'date' => '2025-02-20',
        ]);

        $trend = $this->service->getMonthlyTrend($this->user, 2025, 4);

        $this->assertCount(6, $trend);
        // 最初の要素は2024年11月（6ヶ月前）
        $this->assertSame(2024, $trend[0]['year']);
        $this->assertSame(11, $trend[0]['month']);
        // 最後の要素は2025年4月（当月）
        $this->assertSame(2025, $trend[5]['year']);
        $this->assertSame(4, $trend[5]['month']);
        $this->assertSame(200000.0, $trend[5]['income']);
        $this->assertSame(150000.0, $trend[5]['expense']);
        // データのない月は0
        $this->assertSame(0.0, $trend[0]['income']);
        $this->assertSame(0.0, $trend[0]['expense']);
        // 2025年2月
        $this->assertSame(180000.0, $trend[3]['income']);
    }

    public function test_get_monthly_trend_handles_year_boundary_correctly(): void
    {
        // 年をまたぐケース: 2025年1月を基準に6ヶ月前 = 2024年8月
        $trend = $this->service->getMonthlyTrend($this->user, 2025, 1);

        $this->assertCount(6, $trend);
        $this->assertSame(2024, $trend[0]['year']);
        $this->assertSame(8, $trend[0]['month']);
        $this->assertSame(2025, $trend[5]['year']);
        $this->assertSame(1, $trend[5]['month']);
    }

    public function test_get_monthly_trend_excludes_other_users_transactions(): void
    {
        $otherUser = User::factory()->create();

        Transaction::factory()->create([
            'user_id' => $this->user->id,
            'type' => 'income',
            'amount' => 200000,
            'date' => '2025-04-15',
        ]);
        Transaction::factory()->create([
            'user_id' => $otherUser->id,
            'type' => 'income',
            'amount' => 999999,
            'date' => '2025-04-10',
        ]);

        $trend = $this->service->getMonthlyTrend($this->user, 2025, 4);

        // 別ユーザーの income は含まれない
        $this->assertSame(200000.0, $trend[5]['income']);
    }
}
