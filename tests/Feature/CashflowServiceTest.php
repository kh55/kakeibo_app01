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

    public function test_calculate_balance_returns_zero_initial_when_no_prior_entries(): void
    {
        $user = User::factory()->create();
        CashflowEntry::factory()->create([
            'user_id' => $user->id,
            'date' => '2026-05-10',
            'name' => '給料',
            'income_amount' => 300000,
            'expense_amount' => 0,
        ]);

        $service = new CashflowService;
        $result = $service->calculateBalance(
            $user,
            Carbon::parse('2026-05-01'),
            Carbon::parse('2026-05-31'),
        );

        $this->assertCount(1, $result);
        $this->assertSame(300000.0, (float) $result[0]['balance']);
    }

    public function test_calculate_balance_includes_prior_entries_as_initial_balance(): void
    {
        $user = User::factory()->create();

        CashflowEntry::factory()->create([
            'user_id' => $user->id,
            'date' => '2026-04-10',
            'name' => '給料(前月)',
            'income_amount' => 100000,
            'expense_amount' => 0,
        ]);
        CashflowEntry::factory()->create([
            'user_id' => $user->id,
            'date' => '2026-04-20',
            'name' => '家賃(前月)',
            'income_amount' => 0,
            'expense_amount' => 30000,
        ]);

        CashflowEntry::factory()->create([
            'user_id' => $user->id,
            'date' => '2026-05-15',
            'name' => '電気代',
            'income_amount' => 0,
            'expense_amount' => 5000,
        ]);

        $service = new CashflowService;
        $result = $service->calculateBalance(
            $user,
            Carbon::parse('2026-05-01'),
            Carbon::parse('2026-05-31'),
        );

        $this->assertCount(1, $result);
        $this->assertSame(65000.0, (float) $result[0]['balance']);
    }

    public function test_calculate_balance_excludes_other_users_prior_entries(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();

        CashflowEntry::factory()->create([
            'user_id' => $other->id,
            'date' => '2026-04-10',
            'name' => '他人の収入',
            'income_amount' => 999999,
            'expense_amount' => 0,
        ]);

        CashflowEntry::factory()->create([
            'user_id' => $user->id,
            'date' => '2026-05-15',
            'name' => '電気代',
            'income_amount' => 0,
            'expense_amount' => 5000,
        ]);

        $service = new CashflowService;
        $result = $service->calculateBalance(
            $user,
            Carbon::parse('2026-05-01'),
            Carbon::parse('2026-05-31'),
        );

        $this->assertCount(1, $result);
        $this->assertSame(-5000.0, (float) $result[0]['balance']);
    }
}
