<?php

namespace Tests\Feature;

use App\Models\Budget;
use App\Models\Category;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BudgetControllerTest extends TestCase
{
    use RefreshDatabase;

    /**
     * 一覧取得時に指定年月・当該ユーザーの支出をカテゴリ別に集計し、
     * 各予算に実績額・残り・超過有無が付与されること。
     */
    public function test_index_attaches_actual_remaining_and_over_budget_to_each_budget(): void
    {
        $user = User::factory()->create();
        $category = Category::create([
            'user_id' => $user->id,
            'name' => '食費',
            'type' => 'expense',
            'sort_order' => 0,
        ]);
        Budget::create([
            'user_id' => $user->id,
            'year' => 2025,
            'month' => 1,
            'category_id' => $category->id,
            'amount' => 10000,
        ]);

        $response = $this->actingAs($user)->get('/budgets?year=2025&month=1');

        $response->assertOk();
        $response->assertViewHas('budgets');
        $budgets = $response->viewData('budgets');
        $this->assertCount(1, $budgets);
        $budget = $budgets->first();
        // 取引なし → 実績0、残り=予算額、超過なし
        $this->assertSame(0, (int) $budget->actual_amount);
        $this->assertSame(10000, (int) $budget->remaining);
        $this->assertFalse($budget->is_over_budget);
    }

    /**
     * 指定年月・当該ユーザーの支出取引がある場合、カテゴリ別合計が実績として付与されること。
     */
    public function test_index_attaches_actual_from_expense_transactions_in_month(): void
    {
        $user = User::factory()->create();
        $category = Category::create([
            'user_id' => $user->id,
            'name' => '食費',
            'type' => 'expense',
            'sort_order' => 0,
        ]);
        Budget::create([
            'user_id' => $user->id,
            'year' => 2025,
            'month' => 1,
            'category_id' => $category->id,
            'amount' => 10000,
        ]);
        Transaction::withoutEvents(function () use ($user, $category) {
            Transaction::create([
                'user_id' => $user->id,
                'date' => '2025-01-15',
                'type' => 'expense',
                'account_id' => null,
                'category_id' => $category->id,
                'name' => '食費',
                'amount' => 3000,
            ]);
            Transaction::create([
                'user_id' => $user->id,
                'date' => '2025-01-20',
                'type' => 'expense',
                'account_id' => null,
                'category_id' => $category->id,
                'name' => '食費2',
                'amount' => 2000,
            ]);
        });

        $response = $this->actingAs($user)->get('/budgets?year=2025&month=1');

        $response->assertOk();
        $budgets = $response->viewData('budgets');
        $budget = $budgets->first();
        $this->assertSame(5000, (int) $budget->actual_amount);
        $this->assertSame(5000, (int) $budget->remaining);
        $this->assertFalse($budget->is_over_budget);
    }

    /**
     * 実績が予算を超える場合、残りが負で超過フラグが true になること。
     */
    public function test_index_sets_over_budget_when_actual_exceeds_amount(): void
    {
        $user = User::factory()->create();
        $category = Category::create([
            'user_id' => $user->id,
            'name' => '食費',
            'type' => 'expense',
            'sort_order' => 0,
        ]);
        Budget::create([
            'user_id' => $user->id,
            'year' => 2025,
            'month' => 1,
            'category_id' => $category->id,
            'amount' => 10000,
        ]);
        Transaction::withoutEvents(function () use ($user, $category) {
            Transaction::create([
                'user_id' => $user->id,
                'date' => '2025-01-10',
                'type' => 'expense',
                'account_id' => null,
                'category_id' => $category->id,
                'name' => '食費',
                'amount' => 15000,
            ]);
        });

        $response = $this->actingAs($user)->get('/budgets?year=2025&month=1');

        $response->assertOk();
        $budgets = $response->viewData('budgets');
        $budget = $budgets->first();
        $this->assertSame(15000, (int) $budget->actual_amount);
        $this->assertSame(-5000, (int) $budget->remaining);
        $this->assertTrue($budget->is_over_budget);
    }

    /**
     * 一覧画面に実績額・残り・達成率または超過の列が表示され、
     * 取引がないカテゴリは実績を 0 で表示すること。残りが負の場合は「超過」で判別可能に表示すること。
     */
    public function test_index_view_shows_actual_remaining_and_over_or_achievement_columns(): void
    {
        $user = User::factory()->create();
        $category = Category::create([
            'user_id' => $user->id,
            'name' => '食費',
            'type' => 'expense',
            'sort_order' => 0,
        ]);
        Budget::create([
            'user_id' => $user->id,
            'year' => 2025,
            'month' => 1,
            'category_id' => $category->id,
            'amount' => 10000,
        ]);

        $response = $this->actingAs($user)->get('/budgets?year=2025&month=1');

        $response->assertOk();
        $html = $response->getContent();
        $this->assertStringContainsString('実績額', $html);
        $this->assertStringContainsString('残り', $html);
        $this->assertStringContainsString('達成率', $html);
        // 取引なし → 実績 0 表示
        $this->assertStringContainsString('0円', $html);
        // 残り 10,000 円表示
        $this->assertStringContainsString('10,000', $html);
    }

    /**
     * 残りが負の場合は「超過」で判別可能に表示すること。
     */
    public function test_index_view_shows_over_label_when_remaining_is_negative(): void
    {
        $user = User::factory()->create();
        $category = Category::create([
            'user_id' => $user->id,
            'name' => '食費',
            'type' => 'expense',
            'sort_order' => 0,
        ]);
        Budget::create([
            'user_id' => $user->id,
            'year' => 2025,
            'month' => 1,
            'category_id' => $category->id,
            'amount' => 10000,
        ]);
        Transaction::withoutEvents(function () use ($user, $category) {
            Transaction::create([
                'user_id' => $user->id,
                'date' => '2025-01-10',
                'type' => 'expense',
                'account_id' => null,
                'category_id' => $category->id,
                'name' => '食費',
                'amount' => 15000,
            ]);
        });

        $response = $this->actingAs($user)->get('/budgets?year=2025&month=1');

        $response->assertOk();
        $this->assertStringContainsString('超過', $response->getContent());
    }

    /**
     * 新規登録時に同一ユーザー・年・月・カテゴリの予算が既に存在する場合は
     * バリデーションで拒否し、友好的なエラーメッセージを表示する。
     */
    public function test_store_rejects_duplicate_user_year_month_category_with_friendly_message(): void
    {
        $user = User::factory()->create();
        $category = Category::create([
            'user_id' => $user->id,
            'name' => '食費',
            'type' => 'expense',
            'sort_order' => 0,
        ]);
        Budget::create([
            'user_id' => $user->id,
            'year' => 2025,
            'month' => 1,
            'category_id' => $category->id,
            'amount' => 10000,
        ]);

        $response = $this->actingAs($user)->post('/budgets', [
            'year' => 2025,
            'month' => 1,
            'category_id' => $category->id,
            'amount' => 3000,
        ]);

        $response->assertSessionHasErrors('category_id');
        $this->assertStringContainsString(
            'この年月・このカテゴリの予算は既に登録されています',
            session('errors')->first('category_id')
        );
        $this->assertDatabaseCount('budgets', 1);
    }

    /**
     * 編集時に同一ユーザー・年・月・カテゴリの別予算が既に存在する場合は
     * バリデーションで拒否し、友好的なメッセージを表示する。
     */
    public function test_update_rejects_duplicate_user_year_month_category_with_friendly_message(): void
    {
        $user = User::factory()->create();
        $categoryA = Category::create([
            'user_id' => $user->id,
            'name' => '食費',
            'type' => 'expense',
            'sort_order' => 0,
        ]);
        $categoryB = Category::create([
            'user_id' => $user->id,
            'name' => '交通費',
            'type' => 'expense',
            'sort_order' => 1,
        ]);
        $budgetA = Budget::create([
            'user_id' => $user->id,
            'year' => 2025,
            'month' => 1,
            'category_id' => $categoryA->id,
            'amount' => 10000,
        ]);
        $budgetB = Budget::create([
            'user_id' => $user->id,
            'year' => 2025,
            'month' => 1,
            'category_id' => $categoryB->id,
            'amount' => 5000,
        ]);

        $response = $this->actingAs($user)->put('/budgets/'.$budgetB->id, [
            'year' => 2025,
            'month' => 1,
            'category_id' => $categoryA->id,
            'amount' => 2000,
        ]);

        $response->assertSessionHasErrors('category_id');
        $this->assertStringContainsString(
            'この年月・このカテゴリの予算は既に登録されています',
            session('errors')->first('category_id')
        );
        $budgetB->refresh();
        $this->assertSame((int) $categoryB->id, (int) $budgetB->category_id);
    }

    /**
     * 編集時に当該予算の id を除外して重複判定するため、
     * 年・月・カテゴリを変えずに金額だけ変更する場合は成功する。
     */
    public function test_update_allows_same_budget_without_changing_year_month_category(): void
    {
        $user = User::factory()->create();
        $category = Category::create([
            'user_id' => $user->id,
            'name' => '食費',
            'type' => 'expense',
            'sort_order' => 0,
        ]);
        $budget = Budget::create([
            'user_id' => $user->id,
            'year' => 2025,
            'month' => 1,
            'category_id' => $category->id,
            'amount' => 10000,
        ]);

        $response = $this->actingAs($user)->put('/budgets/'.$budget->id, [
            'year' => 2025,
            'month' => 1,
            'category_id' => $category->id,
            'amount' => 15000,
        ]);

        $response->assertRedirect();
        $response->assertSessionHasNoErrors();
        $budget->refresh();
        $this->assertSame('15000.00', $budget->amount);
    }

    // --- Task 3.1: 一覧の年月フィルタ・リダイレクト・当該ユーザーの予算のみ表示 ---

    /**
     * 一覧にアクセスしたとき、パラメータがなければデフォルトで当月の年月を用いて一覧を表示する。
     */
    public function test_index_defaults_to_current_year_month(): void
    {
        $user = User::factory()->create();
        $now = now();

        $response = $this->actingAs($user)->get('/budgets');

        $response->assertOk();
        $response->assertViewHas('year', (int) $now->format('Y'));
        $response->assertViewHas('month', (int) $now->format('n'));
    }

    /**
     * 年・月を指定してフィルタを実行すると、指定年月の予算一覧のみ表示する。
     */
    public function test_index_filters_by_year_and_month(): void
    {
        $user = User::factory()->create();
        $category = Category::create([
            'user_id' => $user->id,
            'name' => '食費',
            'type' => 'expense',
            'sort_order' => 0,
        ]);
        Budget::create([
            'user_id' => $user->id,
            'year' => 2025,
            'month' => 2,
            'category_id' => $category->id,
            'amount' => 10000,
        ]);

        $response = $this->actingAs($user)->get('/budgets?year=2025&month=2');

        $response->assertOk();
        $response->assertViewHas('year', 2025);
        $response->assertViewHas('month', 2);
        $budgets = $response->viewData('budgets');
        $this->assertCount(1, $budgets);
        $this->assertSame(2025, $budgets->first()->year);
        $this->assertSame(2, $budgets->first()->month);
    }

    /**
     * 一覧には当該ユーザーが所有する予算のみ含める。他ユーザーの予算は表示されない。
     */
    public function test_index_shows_only_current_user_budgets(): void
    {
        $userA = User::factory()->create();
        $userB = User::factory()->create();
        $categoryB = Category::create([
            'user_id' => $userB->id,
            'name' => '食費',
            'type' => 'expense',
            'sort_order' => 0,
        ]);
        Budget::create([
            'user_id' => $userB->id,
            'year' => 2025,
            'month' => 1,
            'category_id' => $categoryB->id,
            'amount' => 10000,
        ]);

        $response = $this->actingAs($userA)->get('/budgets?year=2025&month=1');

        $response->assertOk();
        $budgets = $response->viewData('budgets');
        $this->assertCount(0, $budgets);
    }

    /**
     * 新規登録後、その年月の予算一覧へリダイレクトする。
     */
    public function test_store_redirects_to_index_with_year_month(): void
    {
        $user = User::factory()->create();
        $category = Category::create([
            'user_id' => $user->id,
            'name' => '食費',
            'type' => 'expense',
            'sort_order' => 0,
        ]);

        $response = $this->actingAs($user)->post('/budgets', [
            'year' => 2025,
            'month' => 3,
            'category_id' => $category->id,
            'amount' => 5000,
        ]);

        $response->assertRedirect(route('budgets.index', ['year' => 2025, 'month' => 3]));
        $response->assertSessionHas('success');
    }

    /**
     * 編集後、その年月の予算一覧へリダイレクトする。
     */
    public function test_update_redirects_to_index_with_year_month(): void
    {
        $user = User::factory()->create();
        $category = Category::create([
            'user_id' => $user->id,
            'name' => '食費',
            'type' => 'expense',
            'sort_order' => 0,
        ]);
        $budget = Budget::create([
            'user_id' => $user->id,
            'year' => 2025,
            'month' => 4,
            'category_id' => $category->id,
            'amount' => 10000,
        ]);

        $response = $this->actingAs($user)->put('/budgets/'.$budget->id, [
            'year' => 2025,
            'month' => 4,
            'category_id' => $category->id,
            'amount' => 8000,
        ]);

        $response->assertRedirect(route('budgets.index', ['year' => 2025, 'month' => 4]));
        $response->assertSessionHas('success');
    }

    /**
     * 削除後、予算一覧へリダイレクトする。
     */
    public function test_destroy_redirects_to_index(): void
    {
        $user = User::factory()->create();
        $category = Category::create([
            'user_id' => $user->id,
            'name' => '食費',
            'type' => 'expense',
            'sort_order' => 0,
        ]);
        $budget = Budget::create([
            'user_id' => $user->id,
            'year' => 2025,
            'month' => 1,
            'category_id' => $category->id,
            'amount' => 10000,
        ]);

        $response = $this->actingAs($user)->delete('/budgets/'.$budget->id);

        $response->assertRedirect(route('budgets.index'));
        $response->assertSessionHas('success');
        $this->assertDatabaseMissing('budgets', ['id' => $budget->id]);
    }

    // --- Task 3.2: 他ユーザー予算の編集・削除で 403 ---

    /**
     * 他ユーザーが所有する予算の編集画面にアクセスすると 403 を返す。
     */
    public function test_edit_other_users_budget_returns_403(): void
    {
        $userA = User::factory()->create();
        $userB = User::factory()->create();
        $categoryB = Category::create([
            'user_id' => $userB->id,
            'name' => '食費',
            'type' => 'expense',
            'sort_order' => 0,
        ]);
        $budgetB = Budget::create([
            'user_id' => $userB->id,
            'year' => 2025,
            'month' => 1,
            'category_id' => $categoryB->id,
            'amount' => 10000,
        ]);

        $response = $this->actingAs($userA)->get('/budgets/'.$budgetB->id.'/edit');

        $response->assertForbidden();
    }

    /**
     * 他ユーザーが所有する予算の更新をリクエストすると 403 を返す。
     */
    public function test_update_other_users_budget_returns_403(): void
    {
        $userA = User::factory()->create();
        $userB = User::factory()->create();
        $categoryB = Category::create([
            'user_id' => $userB->id,
            'name' => '食費',
            'type' => 'expense',
            'sort_order' => 0,
        ]);
        $budgetB = Budget::create([
            'user_id' => $userB->id,
            'year' => 2025,
            'month' => 1,
            'category_id' => $categoryB->id,
            'amount' => 10000,
        ]);

        $response = $this->actingAs($userA)->put('/budgets/'.$budgetB->id, [
            'year' => 2025,
            'month' => 1,
            'category_id' => $categoryB->id,
            'amount' => 5000,
        ]);

        $response->assertForbidden();
        $budgetB->refresh();
        $this->assertSame('10000.00', $budgetB->amount);
    }

    /**
     * 他ユーザーが所有する予算の削除をリクエストすると 403 を返す。
     */
    public function test_destroy_other_users_budget_returns_403(): void
    {
        $userA = User::factory()->create();
        $userB = User::factory()->create();
        $categoryB = Category::create([
            'user_id' => $userB->id,
            'name' => '食費',
            'type' => 'expense',
            'sort_order' => 0,
        ]);
        $budgetB = Budget::create([
            'user_id' => $userB->id,
            'year' => 2025,
            'month' => 1,
            'category_id' => $categoryB->id,
            'amount' => 10000,
        ]);

        $response = $this->actingAs($userA)->delete('/budgets/'.$budgetB->id);

        $response->assertForbidden();
        $this->assertDatabaseHas('budgets', ['id' => $budgetB->id]);
    }
}
