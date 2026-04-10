# ダッシュボード拡張 設計書

**作成日:** 2026-04-09
**ブランチ:** feature/fixed-budget

---

## 概要

ダッシュボードに以下の3機能を追加する。

| # | 機能 | 概要 |
|---|------|------|
| F | 月切り替えナビゲーション | 前月・翌月へのリンクボタン |
| E | 貯蓄率カード | 当月の収入に対する貯蓄割合（実績値のみ） |
| D | 月次推移グラフ | 直近6ヶ月の収入・支出グループ棒グラフ |

---

## レイアウト

縦積みレイアウト（既存構成への変更を最小化）：

```
[月切り替えナビゲーション]
[収入][支出][収支][繰越残高][貯蓄率]  ← 5列カード
[月次推移グラフ（直近6ヶ月）]
[分類別支出トップ10] [予算 vs 実支出]  ← 既存2列テーブル
```

---

## 変更ファイル

### 1. `resources/views/layouts/app.blade.php`

Chart.js を CDN で追加（Bootstrap JS の直前）：

```html
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
```

### 2. `app/Services/DashboardService.php`

#### 2-1. `getMonthlySummary()` に `savings_rate` を追加

```php
$savingsRate = $income > 0 ? (int) round(($income - $expense) / $income * 100) : null;

return [
    // ... 既存フィールド ...
    'savings_rate' => $savingsRate,
];
```

#### 2-2. 新メソッド `getMonthlyTrend()` を追加

```php
/**
 * Get income/expense for the past N months ending at the given year/month.
 */
public function getMonthlyTrend(User $user, int $year, int $month, int $months = 6): array
```

- 指定された年月を基準に過去 `$months` ヶ月分を計算
- 年をまたぐ場合（例：2024年11月〜2025年4月）も正しく処理
- 戻り値：`[['year' => int, 'month' => int, 'income' => float, 'expense' => float], ...]`（古い順）

### 3. `app/Http/Controllers/DashboardController.php`

`index()` メソッドに追加：

```php
// 月次推移データ
$monthlyTrend = $this->dashboardService->getMonthlyTrend($user, $year, $month);

// 月ナビゲーション URL
$prevDate = Carbon::create($year, $month, 1)->subMonth();
$nextDate = Carbon::create($year, $month, 1)->addMonth();
$now = Carbon::now();

$prevUrl = route('dashboard', ['year' => $prevDate->year, 'month' => $prevDate->month]);
$nextUrl = $nextDate->lessThanOrEqualTo($now->startOfMonth())
    ? route('dashboard', ['year' => $nextDate->year, 'month' => $nextDate->month])
    : null; // 未来月はnull（リンクなし）

return view('dashboard.index', compact(
    // ... 既存変数 ...
    'monthlyTrend',
    'prevUrl',
    'nextUrl',
));
```

### 4. `resources/views/dashboard/index.blade.php`

#### 4-1. 月切り替えナビゲーション（最上部に追加）

```html
<div class="d-flex justify-content-center align-items-center gap-3 mb-4">
    <a href="{{ $prevUrl }}" class="btn btn-outline-secondary btn-sm">&#8592;</a>
    <span class="fw-semibold fs-5">{{ $year }}年{{ $month }}月</span>
    @if($nextUrl)
        <a href="{{ $nextUrl }}" class="btn btn-outline-secondary btn-sm">&#8594;</a>
    @else
        <span class="btn btn-outline-secondary btn-sm disabled">&#8594;</span>
    @endif
</div>
```

#### 4-2. 貯蓄率カード（既存4列を5列に変更）

- `col-md-3` × 4 → `col` × 5（Bootstrap auto-width）
- 貯蓄率カードを末尾に追加
- 表示値：`$summary['savings_rate'] !== null ? $summary['savings_rate'] . '%' : '—'`
- 負値（支出超過）は `text-danger`、正値は `text-primary`

#### 4-3. 月次推移グラフ（2列テーブルの上に追加）

```html
<div class="card mb-4">
    <div class="card-header">
        <h5 class="card-title mb-0">月次推移（直近6ヶ月）</h5>
    </div>
    <div class="card-body">
        <canvas id="monthlyTrendChart"
            data-trend='@json($monthlyTrend)'>
        </canvas>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const canvas = document.getElementById('monthlyTrendChart');
    const trend = JSON.parse(canvas.dataset.trend);
    const labels = trend.map(r => r.month + '月');
    new Chart(canvas, {
        type: 'bar',
        data: {
            labels,
            datasets: [
                { label: '収入', data: trend.map(r => r.income), backgroundColor: '#0d6efd' },
                { label: '支出', data: trend.map(r => r.expense), backgroundColor: '#dc3545' },
            ]
        },
        options: {
            responsive: true,
            plugins: { legend: { position: 'bottom' } },
            scales: { y: { beginAtZero: true } }
        }
    });
});
</script>
```

---

## エッジケース

| ケース | 対応 |
|--------|------|
| 収入が0のとき（貯蓄率） | `null` を返し、ビューで `—` を表示 |
| 翌月が未来のとき（月ナビ） | `$nextUrl = null` → ボタンを `disabled` 表示 |
| グラフ期間が年をまたぐとき | `getMonthlyTrend()` 内で Carbon を使い正しく前月を計算 |
| `$monthlyTrend` が空のとき | Chart.js は空データでも描画エラーにならない |

---

## テスト方針

- `DashboardServiceTest` に `getMonthlyTrend()` のユニットテストを追加
  - 通常ケース（同年内6ヶ月）
  - 年をまたぐケース（例：2024年10月〜2025年3月）
  - データが存在しない月は income/expense が 0 になること
- `getMonthlySummary()` の `savings_rate` のテスト
  - 収入あり → 正しい割合
  - 収入0 → null
  - 支出超過 → 負の値

---

## 実装しないこと（スコープ外）

- 資産形成関連（純資産・FIRE達成度など）は今回対象外
- 貯蓄目標の設定機能は不要（実績値のみ）
- 月次推移グラフの年跨ぎ表示（ラベルは「N月」のみ、年表示なし）
