# Fixed Budget Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** `budgets` テーブルから `year`/`month` を削除し、カテゴリごとに固定の月次予算額を1つ登録できるようにする。

**Architecture:** マイグレーションで `budgets` テーブルを `(user_id, category_id, amount)` に変更。BudgetController・DashboardService・ビューを固定予算対応に更新。月フィルタは実績額の絞り込みにのみ使う。

**Tech Stack:** Laravel 11, PHP, Blade, PHPUnit (Feature Tests)

---

## File Map

| ファイル | 変更種別 | 内容 |
|---------|---------|------|
| `database/migrations/XXXX_drop_year_month_from_budgets.php` | Create | year/month カラム削除 |
| `app/Models/Budget.php` | Modify | $fillable/$casts から year/month 削除 |
| `app/Http/Controllers/BudgetController.php` | Modify | year/month バリデーション削除、クエリ変更 |
| `app/Services/DashboardService.php` | Modify | getBudgetComparison から年月フィルタ削除 |
| `resources/views/budgets/create.blade.php` | Modify | 年月フィールド削除 |
| `resources/views/budgets/edit.blade.php` | Modify | 年月フィールド削除 |
| `tests/Feature/BudgetControllerTest.php` | Modify | 固定予算に合わせてテスト全面書き換え |

---

## Task 1: マイグレーション作成・実行

**Files:**
- Create: `database/migrations/2026_04_07_000000_drop_year_month_from_budgets.php`

- [ ] **Step 1: マイグレーションファイルを作成する**

```bash
cd /path/to/project && php artisan make:migration drop_year_month_from_budgets
```

作成されたファイルを以下の内容で上書き（ファイル名の日付部分は artisan が生成したものに合わせる）:

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('budgets', function (Blueprint $table) {
            $table->dropUnique(['user_id', 'year', 'month', 'category_id']);
            $table->dropIndex(['user_id', 'year', 'month']);
            $table->dropColumn(['year', 'month']);
            $table->unique(['user_id', 'category_id']);
        });
    }

    public function down(): void
    {
        Schema::table('budgets', function (Blueprint $table) {
            $table->dropUnique(['user_id', 'category_id']);
            $table->year('year')->after('user_id');
            $table->unsignedTinyInteger('month')->after('year');
            $table->unique(['user_id', 'year', 'month', 'category_id']);
            $table->index(['user_id', 'year', 'month']);
        });
    }
};
```

- [ ] **Step 2: マイグレーションを実行する**

```bash
php artisan migrate
```

期待出力: `Running migrations... drop_year_month_from_budgets ... DONE`

- [ ] **Step 3: コミット**

```bash
git add database/migrations/
git commit -m "chore: migration to drop year/month from budgets table"
```

---

## Task 2: Budget モデル更新

**Files:**
- Modify: `app/Models/Budget.php`

- [ ] **Step 1: `$fillable` と `$casts` から `year`/`month` を削除する**

`app/Models/Budget.php` を以下に変更:

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Budget extends Model
{
    protected $fillable = [
        'user_id',
        'category_id',
        'amount',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }
}
```

- [ ] **Step 2: コミット**

```bash
git add app/Models/Budget.php
git commit -m "feat: remove year/month from Budget model"
```

---

## Task 3: BudgetControllerTest を固定予算対応に書き換える

**Files:**
- Modify: `tests/Feature/BudgetControllerTest.php`

- [ ] **Step 1: テストファイルを以下の内容で全面書き換えする**

```php
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
     * 一覧取得時に固定予算と指定月の実績を比較できること（実績なし）。
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
            'category_id' => $category->id,
            'amount' => 10000,
        ]);

        $response = $this->actingAs($user)->get('/budgets?year=2025&month=1');

        $response->assertOk();
        $response->assertViewHas('budgets');
        $budgets = $response->viewData('budgets');
        $this->assertCount(1, $budgets);
        $budget = $budgets->first();
        $this->assertSame(0, (int) $budget->actual_amount);
        $this->assertSame(10000, (int) $budget->remaining);
        $this->assertFalse($budget->is_over_budget);
    }

    /**
     * 指定月の支出取引がある場合、カテゴリ別合計が実績として付与されること。
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
     * 一覧画面に実績額・残り・達成率または超過の列が表示されること。
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
            'category_id' => $category->id,
            'amount' => 10000,
        ]);

        $response = $this->actingAs($user)->get('/budgets?year=2025&month=1');

        $response->assertOk();
        $html = $response->getContent();
        $this->assertStringContainsString('実績額', $html);
        $this->assertStringContainsString('残り', $html);
        $this->assertStringContainsString('達成率', $html);
        $this->assertStringContainsString('0円', $html);
        $this->assertStringContainsString('10,000', $html);
    }

    /**
     * 残りが負の場合は「超過」ラベルが表示されること。
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
     * 月フィルタを変えると実績額が変わり、予算額は変わらないこと。
     */
    public function test_index_actual_changes_by_month_but_budget_stays_fixed(): void
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
                'amount' => 4000,
            ]);
        });

        // 1月: 実績 4000
        $response = $this->actingAs($user)->get('/budgets?year=2025&month=1');
        $budget1 = $response->viewData('budgets')->first();
        $this->assertSame(4000, (int) $budget1->actual_amount);
        $this->assertSame(10000, (int) $budget1->amount);

        // 2月: 実績 0、予算は同じ 10000
        $response2 = $this->actingAs($user)->get('/budgets?year=2025&month=2');
        $budget2 = $response2->viewData('budgets')->first();
        $this->assertSame(0, (int) $budget2->actual_amount);
        $this->assertSame(10000, (int) $budget2->amount);
    }

    /**
     * 新規登録時に同一ユーザー・カテゴリの予算が既に存在する場合はバリデーションで拒否する。
     */
    public function test_store_rejects_duplicate_user_category_with_friendly_message(): void
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
            'category_id' => $category->id,
            'amount' => 10000,
        ]);

        $response = $this->actingAs($user)->post('/budgets', [
            'category_id' => $category->id,
            'amount' => 3000,
        ]);

        $response->assertSessionHasErrors('category_id');
        $this->assertStringContainsString(
            'このカテゴリの予算は既に登録されています',
            session('errors')->first('category_id')
        );
        $this->assertDatabaseCount('budgets', 1);
    }

    /**
     * 新規登録後、予算一覧へリダイレクトする。
     */
    public function test_store_redirects_to_index(): void
    {
        $user = User::factory()->create();
        $category = Category::create([
            'user_id' => $user->id,
            'name' => '食費',
            'type' => 'expense',
            'sort_order' => 0,
        ]);

        $response = $this->actingAs($user)->post('/budgets', [
            'category_id' => $category->id,
            'amount' => 5000,
        ]);

        $response->assertRedirect(route('budgets.index'));
        $response->assertSessionHas('success');
        $this->assertDatabaseHas('budgets', [
            'user_id' => $user->id,
            'category_id' => $category->id,
            'amount' => 5000,
        ]);
    }

    /**
     * 編集時に同一ユーザー・カテゴリの別予算が存在する場合はバリデーションで拒否する。
     */
    public function test_update_rejects_duplicate_user_category_with_friendly_message(): void
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
        Budget::create([
            'user_id' => $user->id,
            'category_id' => $categoryA->id,
            'amount' => 10000,
        ]);
        $budgetB = Budget::create([
            'user_id' => $user->id,
            'category_id' => $categoryB->id,
            'amount' => 5000,
        ]);

        $response = $this->actingAs($user)->put('/budgets/'.$budgetB->id, [
            'category_id' => $categoryA->id,
            'amount' => 2000,
        ]);

        $response->assertSessionHasErrors('category_id');
        $this->assertStringContainsString(
            'このカテゴリの予算は既に登録されています',
            session('errors')->first('category_id')
        );
        $budgetB->refresh();
        $this->assertSame((int) $categoryB->id, (int) $budgetB->category_id);
    }

    /**
     * 編集時にカテゴリを変えずに金額だけ変更する場合は成功する。
     */
    public function test_update_allows_same_budget_amount_change(): void
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
            'category_id' => $category->id,
            'amount' => 10000,
        ]);

        $response = $this->actingAs($user)->put('/budgets/'.$budget->id, [
            'category_id' => $category->id,
            'amount' => 15000,
        ]);

        $response->assertRedirect(route('budgets.index'));
        $response->assertSessionHasNoErrors();
        $budget->refresh();
        $this->assertSame('15000.00', $budget->amount);
    }

    /**
     * 一覧にアクセスしたとき、パラメータがなければデフォルトで当月の年月を用いる。
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
            'category_id' => $categoryB->id,
            'amount' => 10000,
        ]);

        $response = $this->actingAs($userA)->get('/budgets');

        $response->assertOk();
        $this->assertCount(0, $response->viewData('budgets'));
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
            'category_id' => $category->id,
            'amount' => 10000,
        ]);

        $response = $this->actingAs($user)->delete('/budgets/'.$budget->id);

        $response->assertRedirect(route('budgets.index'));
        $response->assertSessionHas('success');
        $this->assertDatabaseMissing('budgets', ['id' => $budget->id]);
    }

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
            'category_id' => $categoryB->id,
            'amount' => 10000,
        ]);

        $response = $this->actingAs($userA)->put('/budgets/'.$budgetB->id, [
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
            'category_id' => $categoryB->id,
            'amount' => 10000,
        ]);

        $response = $this->actingAs($userA)->delete('/budgets/'.$budgetB->id);

        $response->assertForbidden();
        $this->assertDatabaseHas('budgets', ['id' => $budgetB->id]);
    }
}
```

- [ ] **Step 2: テストを実行し、失敗を確認する（まだコントローラーが古いため）**

```bash
php artisan test tests/Feature/BudgetControllerTest.php
```

期待: 複数テストが FAIL（`year`/`month` バリデーションエラーや重複チェックエラーで失敗）

- [ ] **Step 3: コミット**

```bash
git add tests/Feature/BudgetControllerTest.php
git commit -m "test: rewrite BudgetControllerTest for fixed budget"
```

---

## Task 4: BudgetController を固定予算対応に更新する

**Files:**
- Modify: `app/Http/Controllers/BudgetController.php`

- [ ] **Step 1: BudgetController を以下の内容に更新する**

```php
<?php

namespace App\Http\Controllers;

use App\Models\Budget;
use App\Models\Transaction;
use Carbon\Carbon;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class BudgetController extends Controller
{
    /**
     * Display a listing of the resource.
     * 固定予算の一覧を取得し、指定年月の実績・残り・超過有無を付与する。
     */
    public function index(Request $request)
    {
        $user = Auth::user();
        $year = (int) $request->get('year', Carbon::now()->year);
        $month = (int) $request->get('month', Carbon::now()->month);

        $budgets = Budget::where('user_id', $user->id)
            ->with('category')
            ->get();

        $totalsByCategory = Transaction::where('user_id', $user->id)
            ->forMonth($year, $month)
            ->expense()
            ->selectRaw('category_id, sum(amount) as total')
            ->groupBy('category_id')
            ->pluck('total', 'category_id');

        foreach ($budgets as $budget) {
            $actual = (float) ($totalsByCategory[$budget->category_id] ?? 0);
            $budget->actual_amount = $actual;
            $budget->remaining = (float) $budget->amount - $actual;
            $budget->is_over_budget = $budget->remaining < 0;
        }

        return view('budgets.index', compact('budgets', 'year', 'month'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(Request $request)
    {
        $user = Auth::user();
        $categories = $user->categories()->where('type', 'expense')->orderBy('sort_order')->get();

        return view('budgets.create', compact('categories'));
    }

    /**
     * Store a newly created resource in storage.
     * 同一 user・カテゴリの重複はバリデーションで拒否する。
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'category_id' => [
                'required',
                'exists:categories,id',
                Rule::unique('budgets', 'category_id')
                    ->where('user_id', Auth::id()),
            ],
            'amount' => 'required|numeric|min:0',
        ], [
            'category_id.unique' => 'このカテゴリの予算は既に登録されています。',
        ]);

        $validated['user_id'] = Auth::id();

        try {
            Budget::create($validated);
        } catch (QueryException $e) {
            if ((int) ($e->errorInfo[1] ?? 0) === 1062 || str_contains($e->getMessage(), 'Duplicate entry')) {
                throw ValidationException::withMessages([
                    'category_id' => ['このカテゴリの予算は既に登録されています。'],
                ]);
            }
            throw $e;
        }

        return redirect()->route('budgets.index')
            ->with('success', '予算を登録しました。');
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Budget $budget)
    {
        $this->authorize('update', $budget);
        $user = Auth::user();
        $categories = $user->categories()->where('type', 'expense')->orderBy('sort_order')->get();

        return view('budgets.edit', compact('budget', 'categories'));
    }

    /**
     * Update the specified resource in storage.
     * 同一 user・カテゴリの別予算が存在する場合はバリデーションで拒否する。当該 budget の id は除外。
     */
    public function update(Request $request, Budget $budget)
    {
        $this->authorize('update', $budget);

        $validated = $request->validate([
            'category_id' => [
                'required',
                'exists:categories,id',
                Rule::unique('budgets', 'category_id')
                    ->where('user_id', Auth::id())
                    ->ignore($budget->id),
            ],
            'amount' => 'required|numeric|min:0',
        ], [
            'category_id.unique' => 'このカテゴリの予算は既に登録されています。',
        ]);

        try {
            $budget->update($validated);
        } catch (QueryException $e) {
            if ((int) ($e->errorInfo[1] ?? 0) === 1062 || str_contains($e->getMessage(), 'Duplicate entry')) {
                throw ValidationException::withMessages([
                    'category_id' => ['このカテゴリの予算は既に登録されています。'],
                ]);
            }
            throw $e;
        }

        return redirect()->route('budgets.index')
            ->with('success', '予算を更新しました。');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Budget $budget)
    {
        $this->authorize('delete', $budget);
        $budget->delete();

        return redirect()->route('budgets.index')
            ->with('success', '予算を削除しました。');
    }
}
```

- [ ] **Step 2: テストを実行して通ることを確認する**

```bash
php artisan test tests/Feature/BudgetControllerTest.php
```

期待: 全テスト PASS

- [ ] **Step 3: コミット**

```bash
git add app/Http/Controllers/BudgetController.php
git commit -m "feat: update BudgetController for fixed budget (remove year/month)"
```

---

## Task 5: DashboardService を固定予算対応に更新する

**Files:**
- Modify: `app/Services/DashboardService.php`

- [ ] **Step 1: `getBudgetComparison` から年月による予算フィルタを削除する**

`app/Services/DashboardService.php` の `getBudgetComparison` メソッドを以下に変更:

```php
/**
 * Get budget vs actual comparison.
 */
public function getBudgetComparison(User $user, int $year, int $month): array
{
    $budgets = Budget::where('user_id', $user->id)
        ->with('category')
        ->get();

    $actuals = Transaction::where('user_id', $user->id)
        ->forMonth($year, $month)
        ->expense()
        ->select('category_id', DB::raw('SUM(amount) as total'))
        ->groupBy('category_id')
        ->pluck('total', 'category_id');

    return $budgets->map(function ($budget) use ($actuals) {
        $actual = $actuals->get($budget->category_id, 0);

        return [
            'category_name' => $budget->category->name,
            'budget' => $budget->amount,
            'actual' => $actual,
            'difference' => $budget->amount - $actual,
        ];
    })->toArray();
}
```

- [ ] **Step 2: 全テストを実行して既存テストが壊れていないことを確認する**

```bash
php artisan test
```

期待: 全テスト PASS

- [ ] **Step 3: コミット**

```bash
git add app/Services/DashboardService.php
git commit -m "feat: update DashboardService to use fixed budgets"
```

---

## Task 6: ビュー更新（年月フィールド削除）

**Files:**
- Modify: `resources/views/budgets/create.blade.php`
- Modify: `resources/views/budgets/edit.blade.php`

- [ ] **Step 1: `budgets/create.blade.php` を更新する（年・月フィールド削除）**

```blade
<x-app-layout>
    <x-slot name="header">
        <h2 class="h4">予算登録</h2>
    </x-slot>

    <div class="card">
        <div class="card-body">
            <form method="POST" action="{{ route('budgets.store') }}">
                @csrf
                <div class="mb-3">
                    <label for="category_id" class="form-label">分類</label>
                    <select name="category_id" id="category_id" class="form-select" required>
                        @foreach($categories as $category)
                        <option value="{{ $category->id }}" {{ old('category_id') == $category->id ? 'selected' : '' }}>
                            {{ $category->name }}
                        </option>
                        @endforeach
                    </select>
                    @error('category_id')
                        <div class="text-danger small mt-1">{{ $message }}</div>
                    @enderror
                </div>
                <div class="mb-3">
                    <label for="amount" class="form-label">予算額</label>
                    <input type="number" name="amount" id="amount" class="form-control" value="{{ old('amount') }}" min="0" step="0.01" required>
                </div>
                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-primary">登録</button>
                    <a href="{{ route('budgets.index') }}" class="btn btn-secondary">キャンセル</a>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>
```

- [ ] **Step 2: `budgets/edit.blade.php` を更新する（年・月フィールド削除）**

```blade
<x-app-layout>
    <x-slot name="header">
        <h2 class="h4">予算編集</h2>
    </x-slot>

    <div class="card">
        <div class="card-body">
            <form method="POST" action="{{ route('budgets.update', $budget) }}">
                @csrf
                @method('PUT')
                <div class="mb-3">
                    <label for="category_id" class="form-label">分類</label>
                    <select name="category_id" id="category_id" class="form-select" required>
                        @foreach($categories as $category)
                        <option value="{{ $category->id }}" {{ old('category_id', $budget->category_id) == $category->id ? 'selected' : '' }}>
                            {{ $category->name }}
                        </option>
                        @endforeach
                    </select>
                    @error('category_id')
                        <div class="text-danger small mt-1">{{ $message }}</div>
                    @enderror
                </div>
                <div class="mb-3">
                    <label for="amount" class="form-label">予算額</label>
                    <input type="number" name="amount" id="amount" class="form-control" value="{{ old('amount', $budget->amount) }}" min="0" step="0.01" required>
                </div>
                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-primary">更新</button>
                    <a href="{{ route('budgets.index') }}" class="btn btn-secondary">キャンセル</a>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>
```

- [ ] **Step 3: 全テストを実行して確認する**

```bash
php artisan test
```

期待: 全テスト PASS

- [ ] **Step 4: コミット**

```bash
git add resources/views/budgets/create.blade.php resources/views/budgets/edit.blade.php
git commit -m "feat: remove year/month fields from budget create/edit views"
```

---

## 完了確認

全タスク完了後、以下で最終確認する:

```bash
php artisan test
```

期待: 全テスト PASS、エラーなし
