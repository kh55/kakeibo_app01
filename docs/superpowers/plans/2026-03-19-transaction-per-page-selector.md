# Transaction Per-Page Selector Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** 取引明細ページ（`/transactions`）のページネーション表示件数をURLパラメータ `per_page` で選択できるようにする（20 / 50 / 100件）。

**Architecture:** コントローラーで `per_page` クエリパラメータを受け取り `paginate()` に渡す。ビューのフィルターフォームにセレクトを追加し、ページネーションリンクに全クエリパラメータを引き継ぐ。

**Tech Stack:** Laravel 11, Blade, Bootstrap 5

---

## File Map

| ファイル | 変更種別 | 内容 |
|----------|----------|------|
| `app/Http/Controllers/TransactionController.php` | Modify | `per_page` パラメータの取得・バリデーション・paginate渡し |
| `resources/views/transactions/index.blade.php` | Modify | `per_page` セレクト追加、ページネーションリンク修正 |
| `tests/Feature/TransactionPerPageTest.php` | Create | per_page 機能のフィーチャーテスト |

---

### Task 1: フィーチャーテストを書く

**Files:**
- Create: `tests/Feature/TransactionPerPageTest.php`

- [ ] **Step 1: テストファイルを作成する**

```php
<?php

namespace Tests\Feature;

use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TransactionPerPageTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
        // 25件の取引を作成（20件選択時にページ2が存在することを確認するため）
        Transaction::factory()->count(25)->create([
            'user_id' => $this->user->id,
            'date' => now()->startOfMonth(),
        ]);
    }

    public function test_default_per_page_is_50(): void
    {
        $response = $this->actingAs($this->user)
            ->get(route('transactions.index'));

        $response->assertOk();
        $response->assertViewHas('perPage', 50);
    }

    public function test_per_page_20_shows_correct_count(): void
    {
        $response = $this->actingAs($this->user)
            ->get(route('transactions.index', ['per_page' => 20]));

        $response->assertOk();
        $response->assertViewHas('perPage', 20);
        $this->assertCount(20, $response->viewData('transactions')->items());
    }

    public function test_per_page_100_is_valid(): void
    {
        $response = $this->actingAs($this->user)
            ->get(route('transactions.index', ['per_page' => 100]));

        $response->assertOk();
        $response->assertViewHas('perPage', 100);
    }

    public function test_invalid_per_page_falls_back_to_50(): void
    {
        $response = $this->actingAs($this->user)
            ->get(route('transactions.index', ['per_page' => 999]));

        $response->assertOk();
        $response->assertViewHas('perPage', 50);
    }

    public function test_string_per_page_falls_back_to_50(): void
    {
        $response = $this->actingAs($this->user)
            ->get(route('transactions.index', ['per_page' => 'abc']));

        $response->assertOk();
        $response->assertViewHas('perPage', 50);
    }
}
```

- [ ] **Step 2: テストが失敗することを確認する**

```bash
php artisan test tests/Feature/TransactionPerPageTest.php
```

Expected: FAIL（`perPage` がビューに渡されていない）

---

### Task 2: コントローラーを修正する

**Files:**
- Modify: `app/Http/Controllers/TransactionController.php:15-38`

- [ ] **Step 3: `index` メソッドに `per_page` 処理を追加する**

`index` メソッドの `$type = ...` 行の直後に以下を追加し、`paginate(50)` を `paginate($perPage)` に変更：

```php
public function index(Request $request)
{
    $user = Auth::user();
    $now = Carbon::now();
    $year = (int) $request->get('year', $now->year) ?: $now->year;
    $month = (int) $request->get('month', $now->month) ?: $now->month;
    $month = $month < 1 || $month > 12 ? $now->month : $month;
    $type = $request->get('type'); // income or expense

    $allowedPerPage = [20, 50, 100];
    $perPage = (int) $request->get('per_page', 50);
    if (!in_array($perPage, $allowedPerPage)) {
        $perPage = 50;
    }

    $query = Transaction::where('user_id', $user->id)
        ->forMonth($year, $month)
        ->with(['account', 'category'])
        ->orderBy('date', 'desc')
        ->orderBy('created_at', 'desc');

    if ($type) {
        $query->where('type', $type);
    }

    $transactions = $query->paginate($perPage);
    $isLocal = app()->environment('local');

    return view('transactions.index', compact('transactions', 'year', 'month', 'type', 'isLocal', 'perPage'));
}
```

- [ ] **Step 4: テストを実行して通過することを確認する**

```bash
php artisan test tests/Feature/TransactionPerPageTest.php
```

Expected: PASS（全5テスト）

- [ ] **Step 5: コミットする**

```bash
git add app/Http/Controllers/TransactionController.php tests/Feature/TransactionPerPageTest.php
git commit -m "feat: add per_page parameter to transaction index"
```

---

### Task 3: ビューを修正する

**Files:**
- Modify: `resources/views/transactions/index.blade.php`

- [ ] **Step 6: フィルターフォームに `per_page` セレクトを追加し、ページネーションリンクを修正する**

現在のフォーム部分（`<div class="row g-3">` 〜 `</div>`）を以下に置き換える：

```blade
<div class="row g-3">
    <div class="col-md-2">
        <input type="number" name="year" value="{{ $year }}" class="form-control" placeholder="年">
    </div>
    <div class="col-md-2">
        <input type="number" name="month" value="{{ $month }}" class="form-control" placeholder="月" min="1" max="12">
    </div>
    <div class="col-md-2">
        <select name="type" class="form-select">
            <option value="">すべて</option>
            <option value="income" {{ $type === 'income' ? 'selected' : '' }}>収入</option>
            <option value="expense" {{ $type === 'expense' ? 'selected' : '' }}>支出</option>
        </select>
    </div>
    <div class="col-md-2">
        <select name="per_page" class="form-select">
            <option value="20" {{ $perPage === 20 ? 'selected' : '' }}>20件</option>
            <option value="50" {{ $perPage === 50 ? 'selected' : '' }}>50件</option>
            <option value="100" {{ $perPage === 100 ? 'selected' : '' }}>100件</option>
        </select>
    </div>
    <div class="col-md-auto">
        <button type="submit" class="btn btn-secondary">フィルタ</button>
    </div>
</div>
```

また、ページネーションリンク行を以下に変更する：

```blade
{{ $transactions->appends(request()->query())->links() }}
```

- [ ] **Step 7: ブラウザで動作確認する（手動）**

```bash
php artisan serve
```

1. `http://localhost:8000/transactions` を開く
2. フィルターフォームに「20件 / 50件 / 100件」のセレクトが表示されることを確認
3. 20件を選択して「フィルタ」→ URLに `per_page=20` が付くことを確認
4. ページ2へ移動し、URLに `per_page=20` が引き継がれることを確認

- [ ] **Step 8: コミットする**

```bash
git add resources/views/transactions/index.blade.php
git commit -m "feat: add per_page selector to transaction filter form"
```
