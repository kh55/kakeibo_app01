# Dashboard Enhancements Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** ダッシュボードに月切り替えナビゲーション・貯蓄率カード・月次推移グラフ（直近6ヶ月）の3機能を追加する。

**Architecture:** DashboardService に2つのメソッドを追加し（`savings_rate` フィールドと `getMonthlyTrend()`）、DashboardController でビューへのデータを拡張する。ビューは既存の縦積みレイアウトに3つのUI要素を追加するのみ。

**Tech Stack:** Laravel 11, PHP, Bootstrap 5, Chart.js (CDN), Alpine.js, PHPUnit (SQLite in-memory)

---

## File Map

| ファイル | 変更内容 |
|---------|---------|
| `app/Services/DashboardService.php` | `getMonthlySummary()` に `savings_rate` 追加、`getMonthlyTrend()` 新規追加 |
| `app/Http/Controllers/DashboardController.php` | `index()` に `monthlyTrend`・`prevUrl`・`nextUrl` 追加 |
| `resources/views/layouts/app.blade.php` | Chart.js CDN を1行追加 |
| `resources/views/dashboard/index.blade.php` | 月ナビ・貯蓄率カード・グラフ を追加 |
| `tests/Feature/DashboardServiceTest.php` | 新規作成（ユニットテスト） |

---

## Task 1: `savings_rate` を `getMonthlySummary()` に追加

**Files:**
- Modify: `app/Services/DashboardService.php`
- Create: `tests/Feature/DashboardServiceTest.php`

- [ ] **Step 1: テストファイルを作成して失敗するテストを書く**

`tests/Feature/DashboardServiceTest.php` を新規作成：

```php
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
```

- [ ] **Step 2: テストが失敗することを確認する**

```bash
php artisan test --filter=DashboardServiceTest::test_getMonthlySummary_returns_savings_rate_as_percentage
```

期待: FAIL（`savings_rate` キーが存在しない）

- [ ] **Step 3: `getMonthlySummary()` に `savings_rate` を追加する**

`app/Services/DashboardService.php` の `getMonthlySummary()` メソッドの return 直前に追加：

```php
        $balance = $income - $expense;

        // 繰越 = 当月末日時点の残高 = 当月月初残高 + 当月差額
        $balanceAtStartOfMonth = $this->getCarryoverBalance($user, $year, $month);
        $carryoverBalance = $balanceAtStartOfMonth + $balance;

        $savingsRate = $income > 0 ? (int) round(($income - $expense) / $income * 100) : null;

        return [
            'year' => $year,
            'month' => $month,
            'income' => $income,
            'expense' => $expense,
            'balance' => $balance,
            'carryover_balance' => $carryoverBalance,
            'savings_rate' => $savingsRate,
        ];
```

- [ ] **Step 4: テストが通ることを確認する**

```bash
php artisan test --filter=DashboardServiceTest
```

期待: 3件すべて PASS

- [ ] **Step 5: コミット**

```bash
git add app/Services/DashboardService.php tests/Feature/DashboardServiceTest.php
git commit -m "feat: add savings_rate to getMonthlySummary"
```

---

## Task 2: `getMonthlyTrend()` を DashboardService に追加

**Files:**
- Modify: `app/Services/DashboardService.php`
- Modify: `tests/Feature/DashboardServiceTest.php`

- [ ] **Step 1: 失敗するテストを追加する**

`tests/Feature/DashboardServiceTest.php` に以下のテストを追加：

```php
    public function test_getMonthlyTrend_returns_six_months_of_income_and_expense(): void
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

    public function test_getMonthlyTrend_handles_year_boundary_correctly(): void
    {
        // 年をまたぐケース: 2025年1月を基準に6ヶ月前 = 2024年8月
        $trend = $this->service->getMonthlyTrend($this->user, 2025, 1);

        $this->assertCount(6, $trend);
        $this->assertSame(2024, $trend[0]['year']);
        $this->assertSame(8, $trend[0]['month']);
        $this->assertSame(2025, $trend[5]['year']);
        $this->assertSame(1, $trend[5]['month']);
    }
```

- [ ] **Step 2: テストが失敗することを確認する**

```bash
php artisan test --filter=DashboardServiceTest::test_getMonthlyTrend_returns_six_months_of_income_and_expense
```

期待: FAIL（`getMonthlyTrend` メソッドが存在しない）

- [ ] **Step 3: `getMonthlyTrend()` を実装する**

`app/Services/DashboardService.php` の末尾の `}` の直前に追加：

```php
    /**
     * Get income/expense for the past N months ending at the given year/month.
     *
     * @return array<int, array{year: int, month: int, income: float, expense: float}>
     */
    public function getMonthlyTrend(User $user, int $year, int $month, int $months = 6): array
    {
        $result = [];
        $base = \Carbon\Carbon::create($year, $month, 1);

        for ($i = $months - 1; $i >= 0; $i--) {
            $d = $base->copy()->subMonths($i);
            $y = $d->year;
            $m = $d->month;

            $income = (float) Transaction::where('user_id', $user->id)
                ->forMonth($y, $m)
                ->income()
                ->sum('amount');

            $expense = (float) Transaction::where('user_id', $user->id)
                ->forMonth($y, $m)
                ->expense()
                ->sum('amount');

            $result[] = [
                'year'    => $y,
                'month'   => $m,
                'income'  => $income,
                'expense' => $expense,
            ];
        }

        return $result;
    }
```

- [ ] **Step 4: テストが通ることを確認する**

```bash
php artisan test --filter=DashboardServiceTest
```

期待: 5件すべて PASS

- [ ] **Step 5: コミット**

```bash
git add app/Services/DashboardService.php tests/Feature/DashboardServiceTest.php
git commit -m "feat: add getMonthlyTrend to DashboardService"
```

---

## Task 3: DashboardController を更新する

**Files:**
- Modify: `app/Http/Controllers/DashboardController.php`

- [ ] **Step 1: `index()` に月ナビURLと月次推移データを追加する**

`app/Http/Controllers/DashboardController.php` の `index()` メソッドを以下のように変更する。

変更前（`$upcomingPayments` の行の後）：
```php
        $isLocal = app()->environment('local');
```

変更後：
```php
        // 月次推移データ（直近6ヶ月）
        $monthlyTrend = $this->dashboardService->getMonthlyTrend($user, $year, $month);

        // 月切り替えナビゲーション URL
        $prevDate = Carbon::create($year, $month, 1)->subMonth();
        $nextDate = Carbon::create($year, $month, 1)->addMonth();
        $nextUrl = $nextDate->lessThanOrEqualTo(Carbon::now()->startOfMonth())
            ? route('dashboard', ['year' => $nextDate->year, 'month' => $nextDate->month])
            : null;
        $prevUrl = route('dashboard', ['year' => $prevDate->year, 'month' => $prevDate->month]);

        $isLocal = app()->environment('local');
```

変更前（return view の compact）：
```php
        return view('dashboard.index', compact(
            'summary',
            'categoryExpenses',
            'budgetComparison',
            'upcomingPayments',
            'year',
            'month',
            'isLocal',
            'localTestDataYearMonth',
        ));
```

変更後：
```php
        return view('dashboard.index', compact(
            'summary',
            'categoryExpenses',
            'budgetComparison',
            'upcomingPayments',
            'year',
            'month',
            'isLocal',
            'localTestDataYearMonth',
            'monthlyTrend',
            'prevUrl',
            'nextUrl',
        ));
```

- [ ] **Step 2: ブラウザで動作確認する（コントローラーが正常にビューを返すこと）**

```bash
php artisan serve
```

`http://localhost:8000/dashboard` にアクセスしてエラーが出ないことを確認。（ビューはまだ変更していないので見た目は変わらない）

- [ ] **Step 3: コミット**

```bash
git add app/Http/Controllers/DashboardController.php
git commit -m "feat: pass monthlyTrend and nav URLs to dashboard view"
```

---

## Task 4: ビューに月切り替えナビゲーションを追加する

**Files:**
- Modify: `resources/views/dashboard/index.blade.php`

- [ ] **Step 1: 月ナビゲーションをビュー最上部に追加する**

`resources/views/dashboard/index.blade.php` の `@if(($isLocal ?? false) === true)` の直前に追加：

```blade
    <div class="d-flex justify-content-center align-items-center gap-3 mb-4">
        <a href="{{ $prevUrl }}" class="btn btn-outline-secondary btn-sm">&#8592;</a>
        <span class="fw-semibold fs-5">{{ $year }}年{{ $month }}月</span>
        @if($nextUrl)
            <a href="{{ $nextUrl }}" class="btn btn-outline-secondary btn-sm">&#8594;</a>
        @else
            <span class="btn btn-outline-secondary btn-sm disabled" aria-disabled="true">&#8594;</span>
        @endif
    </div>
```

- [ ] **Step 2: ブラウザで動作確認する**

```bash
php artisan serve
```

`http://localhost:8000/dashboard` にアクセスして確認：
- 「← 2026年4月 →」のようなナビゲーションが表示されること
- 現在月では「→」がグレーアウトしていること
- 「←」クリックで前月のダッシュボードに遷移すること

- [ ] **Step 3: コミット**

```bash
git add resources/views/dashboard/index.blade.php
git commit -m "feat: add month navigation to dashboard"
```

---

## Task 5: ビューに貯蓄率カードを追加する

**Files:**
- Modify: `resources/views/dashboard/index.blade.php`

- [ ] **Step 1: 既存4列カードを5列に変更し、貯蓄率カードを追加する**

`resources/views/dashboard/index.blade.php` のサマリーカードのセクション（`<div class="row mb-4">` から `</div>` まで）を以下に置き換える：

```blade
    <div class="row mb-4">
        <div class="col">
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title">当月収入</h5>
                    <p class="card-text h3 text-success">{{ number_format($summary['income']) }}円</p>
                </div>
            </div>
        </div>
        <div class="col">
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title">当月支出</h5>
                    <p class="card-text h3 text-danger">{{ number_format($summary['expense']) }}円</p>
                </div>
            </div>
        </div>
        <div class="col">
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title">当月収支</h5>
                    <p class="card-text h3 {{ $summary['balance'] >= 0 ? 'text-success' : 'text-danger' }}">
                        {{ number_format($summary['balance']) }}円</p>
                </div>
            </div>
        </div>
        <div class="col">
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title">繰越残高</h5>
                    <p class="card-text h3">{{ number_format($summary['carryover_balance']) }}円</p>
                </div>
            </div>
        </div>
        <div class="col">
            <div class="card border-primary">
                <div class="card-body">
                    <h5 class="card-title">貯蓄率</h5>
                    @if($summary['savings_rate'] === null)
                        <p class="card-text h3 text-muted">—</p>
                    @else
                        <p class="card-text h3 {{ $summary['savings_rate'] >= 0 ? 'text-primary' : 'text-danger' }}">
                            {{ $summary['savings_rate'] }}%</p>
                    @endif
                </div>
            </div>
        </div>
    </div>
```

- [ ] **Step 2: ブラウザで動作確認する**

`http://localhost:8000/dashboard` にアクセスして確認：
- カードが5列で表示されること
- 貯蓄率が「25%」のように表示されること（収入・支出がない月は「—」）

- [ ] **Step 3: コミット**

```bash
git add resources/views/dashboard/index.blade.php
git commit -m "feat: add savings rate card to dashboard"
```

---

## Task 6: Chart.js の追加と月次推移グラフの実装

**Files:**
- Modify: `resources/views/layouts/app.blade.php`
- Modify: `resources/views/dashboard/index.blade.php`

- [ ] **Step 1: Chart.js を CDN で追加する**

`resources/views/layouts/app.blade.php` の Bootstrap JS の `<script>` タグの直前に追加：

```html
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4"></script>

    <!-- Bootstrap 5 JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
```

- [ ] **Step 2: グラフカードをビューに追加する**

`resources/views/dashboard/index.blade.php` の `<div class="row">` （既存の2列テーブルの行）の直前に追加：

```blade
    <div class="card mb-4">
        <div class="card-header">
            <h5 class="card-title mb-0">月次推移（直近6ヶ月）</h5>
        </div>
        <div class="card-body">
            <canvas id="monthlyTrendChart"
                data-trend='@json($monthlyTrend)'></canvas>
        </div>
    </div>

    <script>
    document.addEventListener('DOMContentLoaded', function () {
        const canvas = document.getElementById('monthlyTrendChart');
        const trend = JSON.parse(canvas.dataset.trend);
        const labels = trend.map(function (r) {
            return r.month + '月';
        });
        new Chart(canvas, {
            type: 'bar',
            data: {
                labels: labels,
                datasets: [
                    {
                        label: '収入',
                        data: trend.map(function (r) { return r.income; }),
                        backgroundColor: '#0d6efd',
                    },
                    {
                        label: '支出',
                        data: trend.map(function (r) { return r.expense; }),
                        backgroundColor: '#dc3545',
                    },
                ]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: { position: 'bottom' }
                },
                scales: {
                    y: { beginAtZero: true }
                }
            }
        });
    });
    </script>
```


- [ ] **Step 3: ブラウザで動作確認する**

`http://localhost:8000/dashboard` にアクセスして確認：
- 2列テーブルの上にグラフカードが表示されること
- 収入（青）・支出（赤）のグループ棒グラフが描画されること
- 月ナビで前月に移動してもグラフが正しく更新されること

- [ ] **Step 4: 全テストが通ることを確認する**

```bash
php artisan test
```

期待: すべて PASS

- [ ] **Step 5: コミット**

```bash
git add resources/views/layouts/app.blade.php resources/views/dashboard/index.blade.php
git commit -m "feat: add monthly trend chart to dashboard"
```

---

## 完了チェック

全タスク完了後、以下を確認する：

- [ ] `php artisan test` が全件 PASS
- [ ] ダッシュボードに月ナビゲーションが表示される
- [ ] 貯蓄率カードが5列目に表示される
- [ ] 月次推移グラフ（直近6ヶ月）が描画される
- [ ] 前月に移動すると全情報が更新される
- [ ] 現在月では「→」がグレーアウトされている
