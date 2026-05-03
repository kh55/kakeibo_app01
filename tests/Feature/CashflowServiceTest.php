<?php

namespace Tests\Feature;

use App\Models\CashflowEntry;
use App\Models\User;
use App\Services\CashflowService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CashflowServiceTest extends TestCase
{
    use RefreshDatabase;

    private CashflowService $service;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new CashflowService;
        $this->user = User::factory()->create();
    }

    public function test_calculate_balance_returns_zero_initial_when_no_prior_entries(): void
    {
        CashflowEntry::factory()->create([
            'user_id' => $this->user->id,
            'date' => '2026-05-10',
            'name' => '給料',
            'income_amount' => 300000,
            'expense_amount' => 0,
        ]);

        $result = $this->service->calculateBalance(
            $this->user,
            Carbon::parse('2026-05-01'),
            Carbon::parse('2026-05-31'),
        );

        $this->assertCount(1, $result);
        $this->assertSame(300000.0, (float) $result[0]['balance']);
    }

    public function test_calculate_balance_includes_prior_entries_as_initial_balance(): void
    {
        CashflowEntry::factory()->create([
            'user_id' => $this->user->id,
            'date' => '2026-04-10',
            'name' => '給料(前月)',
            'income_amount' => 100000,
            'expense_amount' => 0,
        ]);
        CashflowEntry::factory()->create([
            'user_id' => $this->user->id,
            'date' => '2026-04-20',
            'name' => '家賃(前月)',
            'income_amount' => 0,
            'expense_amount' => 30000,
        ]);

        CashflowEntry::factory()->create([
            'user_id' => $this->user->id,
            'date' => '2026-05-15',
            'name' => '電気代',
            'income_amount' => 0,
            'expense_amount' => 5000,
        ]);

        $result = $this->service->calculateBalance(
            $this->user,
            Carbon::parse('2026-05-01'),
            Carbon::parse('2026-05-31'),
        );

        $this->assertCount(1, $result);
        $this->assertSame(65000.0, (float) $result[0]['balance']);
    }

    public function test_calculate_balance_excludes_other_users_prior_entries(): void
    {
        $other = User::factory()->create();

        CashflowEntry::factory()->create([
            'user_id' => $other->id,
            'date' => '2026-04-10',
            'name' => '他人の収入',
            'income_amount' => 999999,
            'expense_amount' => 0,
        ]);

        CashflowEntry::factory()->create([
            'user_id' => $this->user->id,
            'date' => '2026-05-15',
            'name' => '電気代',
            'income_amount' => 0,
            'expense_amount' => 5000,
        ]);

        $result = $this->service->calculateBalance(
            $this->user,
            Carbon::parse('2026-05-01'),
            Carbon::parse('2026-05-31'),
        );

        $this->assertCount(1, $result);
        $this->assertSame(-5000.0, (float) $result[0]['balance']);
    }

    public function test_calculate_balance_excludes_entry_on_start_date_from_initial_balance(): void
    {
        CashflowEntry::factory()->create([
            'user_id' => $this->user->id,
            'date' => '2026-05-01',
            'name' => '当日収入',
            'income_amount' => 50000,
            'expense_amount' => 0,
        ]);

        $result = $this->service->calculateBalance(
            $this->user,
            Carbon::parse('2026-05-01'),
            Carbon::parse('2026-05-31'),
        );

        $this->assertCount(1, $result);
        $this->assertSame(50000.0, (float) $result[0]['balance']);
    }
}
