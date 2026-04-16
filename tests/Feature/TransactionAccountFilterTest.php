<?php

namespace Tests\Feature;

use App\Models\Account;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TransactionAccountFilterTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
    }

    public function test_accounts_are_passed_to_view(): void
    {
        Account::create(['user_id' => $this->user->id, 'name' => 'Suica', 'sort_order' => 1]);
        Account::create(['user_id' => $this->user->id, 'name' => '現金', 'sort_order' => 2]);

        $response = $this->actingAs($this->user)
            ->get(route('transactions.index'));

        $response->assertOk();
        $response->assertViewHas('filterAccounts');
        $this->assertCount(2, $response->viewData('filterAccounts'));
    }

    public function test_filter_by_account_shows_only_matching_transactions(): void
    {
        $account = Account::create(['user_id' => $this->user->id, 'name' => 'Suica', 'sort_order' => 1]);
        $otherAccount = Account::create(['user_id' => $this->user->id, 'name' => '現金', 'sort_order' => 2]);

        Transaction::factory()->create([
            'user_id' => $this->user->id,
            'account_id' => $account->id,
            'date' => now()->startOfMonth(),
            'name' => '電車',
        ]);
        Transaction::factory()->create([
            'user_id' => $this->user->id,
            'account_id' => $otherAccount->id,
            'date' => now()->startOfMonth(),
            'name' => 'コンビニ',
        ]);

        $response = $this->actingAs($this->user)
            ->get(route('transactions.index', [
                'year' => now()->year,
                'month' => now()->month,
                'account_id' => $account->id,
            ]));

        $response->assertOk();
        $items = $response->viewData('transactions')->items();
        $this->assertCount(1, $items);
        $this->assertSame('電車', $items[0]->name);
    }

    public function test_filter_by_null_shows_transactions_without_account(): void
    {
        $account = Account::create(['user_id' => $this->user->id, 'name' => 'Suica', 'sort_order' => 1]);

        Transaction::factory()->create([
            'user_id' => $this->user->id,
            'account_id' => $account->id,
            'date' => now()->startOfMonth(),
            'name' => '電車',
        ]);
        Transaction::factory()->create([
            'user_id' => $this->user->id,
            'account_id' => null,
            'date' => now()->startOfMonth(),
            'name' => '未選択の取引',
        ]);

        $response = $this->actingAs($this->user)
            ->get(route('transactions.index', [
                'year' => now()->year,
                'month' => now()->month,
                'account_id' => 'null',
            ]));

        $response->assertOk();
        $items = $response->viewData('transactions')->items();
        $this->assertCount(1, $items);
        $this->assertSame('未選択の取引', $items[0]->name);
    }

    public function test_no_account_filter_shows_all_transactions(): void
    {
        $account = Account::create(['user_id' => $this->user->id, 'name' => 'Suica', 'sort_order' => 1]);

        Transaction::factory()->create([
            'user_id' => $this->user->id,
            'account_id' => $account->id,
            'date' => now()->startOfMonth(),
        ]);
        Transaction::factory()->create([
            'user_id' => $this->user->id,
            'account_id' => null,
            'date' => now()->startOfMonth(),
        ]);

        $response = $this->actingAs($this->user)
            ->get(route('transactions.index', [
                'year' => now()->year,
                'month' => now()->month,
            ]));

        $response->assertOk();
        $this->assertCount(2, $response->viewData('transactions')->items());
    }

    public function test_disabled_accounts_are_included_in_filter_accounts(): void
    {
        Account::create(['user_id' => $this->user->id, 'name' => '有効口座', 'enabled' => true, 'sort_order' => 1]);
        Account::create(['user_id' => $this->user->id, 'name' => '無効口座', 'enabled' => false, 'sort_order' => 2]);

        $response = $this->actingAs($this->user)
            ->get(route('transactions.index'));

        $response->assertOk();
        $this->assertCount(2, $response->viewData('filterAccounts'));
    }
}
