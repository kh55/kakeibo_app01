<?php

namespace Tests\Feature;

use App\Models\Account;
use App\Models\Category;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TransactionSortPreferenceTest extends TestCase
{
    use RefreshDatabase;

    public function test_create_form_sorts_accounts_by_sort_order_when_preference_is_manual(): void
    {
        $user = User::factory()->create(['sort_preference' => 'manual']);
        $accountB = Account::create(['user_id' => $user->id, 'name' => 'B', 'sort_order' => 1, 'enabled' => true]);
        $accountA = Account::create(['user_id' => $user->id, 'name' => 'A', 'sort_order' => 0, 'enabled' => true]);

        $response = $this->actingAs($user)->get(route('transactions.create'));

        $response->assertOk();
        $accounts = $response->viewData('accounts');
        $this->assertEquals($accountA->id, $accounts->first()->id);
        $this->assertEquals($accountB->id, $accounts->last()->id);
    }

    public function test_create_form_sorts_categories_by_sort_order_when_preference_is_manual(): void
    {
        $user = User::factory()->create(['sort_preference' => 'manual']);
        $categoryB = Category::create(['user_id' => $user->id, 'name' => 'B', 'type' => 'expense', 'sort_order' => 1]);
        $categoryA = Category::create(['user_id' => $user->id, 'name' => 'A', 'type' => 'expense', 'sort_order' => 0]);

        $response = $this->actingAs($user)->get(route('transactions.create'));

        $response->assertOk();
        $categories = $response->viewData('categories');
        $this->assertEquals($categoryA->id, $categories->first()->id);
        $this->assertEquals($categoryB->id, $categories->last()->id);
    }

    public function test_create_form_sorts_accounts_by_frequency_when_preference_is_frequency(): void
    {
        $user = User::factory()->create(['sort_preference' => 'frequency']);
        $accountLow = Account::create(['user_id' => $user->id, 'name' => 'Low', 'sort_order' => 0, 'enabled' => true]);
        $accountHigh = Account::create(['user_id' => $user->id, 'name' => 'High', 'sort_order' => 1, 'enabled' => true]);

        // accountHigh を直近3ヶ月に2回使用
        Transaction::create(['user_id' => $user->id, 'account_id' => $accountHigh->id, 'type' => 'expense', 'name' => 'test', 'amount' => 100, 'date' => now()->subWeek()]);
        Transaction::create(['user_id' => $user->id, 'account_id' => $accountHigh->id, 'type' => 'expense', 'name' => 'test', 'amount' => 100, 'date' => now()->subWeek()]);

        $response = $this->actingAs($user)->get(route('transactions.create'));

        $response->assertOk();
        $accounts = $response->viewData('accounts');
        $this->assertEquals($accountHigh->id, $accounts->first()->id);
        $this->assertEquals($accountLow->id, $accounts->last()->id);
    }

    public function test_create_form_sorts_categories_by_frequency_when_preference_is_frequency(): void
    {
        $user = User::factory()->create(['sort_preference' => 'frequency']);
        $categoryLow = Category::create(['user_id' => $user->id, 'name' => 'Low', 'type' => 'expense', 'sort_order' => 0]);
        $categoryHigh = Category::create(['user_id' => $user->id, 'name' => 'High', 'type' => 'expense', 'sort_order' => 1]);

        // categoryHigh を直近3ヶ月に3回使用
        for ($i = 0; $i < 3; $i++) {
            Transaction::create(['user_id' => $user->id, 'category_id' => $categoryHigh->id, 'type' => 'expense', 'name' => 'test', 'amount' => 100, 'date' => now()->subWeek()]);
        }

        $response = $this->actingAs($user)->get(route('transactions.create'));

        $response->assertOk();
        $categories = $response->viewData('categories');
        $this->assertEquals($categoryHigh->id, $categories->first()->id);
        $this->assertEquals($categoryLow->id, $categories->last()->id);
    }

    public function test_frequency_sort_ignores_transactions_older_than_3_months(): void
    {
        $user = User::factory()->create(['sort_preference' => 'frequency']);
        $accountOld = Account::create(['user_id' => $user->id, 'name' => 'Old', 'sort_order' => 0, 'enabled' => true]);
        $accountNew = Account::create(['user_id' => $user->id, 'name' => 'New', 'sort_order' => 1, 'enabled' => true]);

        // accountOld は3ヶ月より前に多数使用（カウント対象外）
        for ($i = 0; $i < 5; $i++) {
            Transaction::create(['user_id' => $user->id, 'account_id' => $accountOld->id, 'type' => 'expense', 'name' => 'test', 'amount' => 100, 'date' => now()->subMonths(4)]);
        }
        // accountNew は直近3ヶ月に1回使用
        Transaction::create(['user_id' => $user->id, 'account_id' => $accountNew->id, 'type' => 'expense', 'name' => 'test', 'amount' => 100, 'date' => now()->subWeek()]);

        $response = $this->actingAs($user)->get(route('transactions.create'));

        $accounts = $response->viewData('accounts');
        $this->assertEquals($accountNew->id, $accounts->first()->id);
    }

    public function test_frequency_sort_falls_back_to_sort_order_when_no_recent_transactions(): void
    {
        $user = User::factory()->create(['sort_preference' => 'frequency']);
        $accountB = Account::create(['user_id' => $user->id, 'name' => 'B', 'sort_order' => 1, 'enabled' => true]);
        $accountA = Account::create(['user_id' => $user->id, 'name' => 'A', 'sort_order' => 0, 'enabled' => true]);

        $response = $this->actingAs($user)->get(route('transactions.create'));

        $accounts = $response->viewData('accounts');
        // 頻度が同じ（0件）なので sort_order が適用される
        $this->assertEquals($accountA->id, $accounts->first()->id);
    }
}
