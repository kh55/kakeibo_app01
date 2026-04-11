# 分類別支出ドーナツグラフ 設計書

**作成日:** 2026-04-11

---

## 概要

ダッシュボードの「分類別支出トップ10」テーブルを、当月の分類別支出ドーナツグラフに置き換える。

---

## 変更内容

### 変更前
- 左カラム：分類別支出トップ10（テーブル、最大10件）
- 右カラム：予算 vs 実支出（テーブル）

### 変更後
- 左カラム：分類別支出ドーナツグラフ（上位7件 ＋ その他）
- 右カラム：予算 vs 実支出（テーブル、変更なし）

---

## 変更ファイル

### 1. `app/Services/DashboardService.php`

`getCategoryExpenseSummary()` の `$limit` デフォルト値を `10` から `null` に変更し、`null` の場合は `->limit()` を適用しない。

```php
public function getCategoryExpenseSummary(User $user, int $year, int $month, ?int $limit = null): array
{
    $query = Transaction::where('user_id', $user->id)
        ->forMonth($year, $month)
        ->expense()
        ->select('category_id', DB::raw('SUM(amount) as total'))
        ->groupBy('category_id')
        ->with('category')
        ->orderByDesc('total');

    if ($limit !== null) {
        $query->limit($limit);
    }

    return $query->get()
        ->map(function ($item) {
            return [
                'category_name' => $item->category?->name ?? '未分類',
                'total' => $item->total,
            ];
        })
        ->toArray();
}
```

### 2. `resources/views/dashboard/index.blade.php`

左カラムのテーブルをドーナツグラフに置き換える。

#### HTML
```html
<div class="col-md-6">
    <div class="card">
        <div class="card-header">
            <h5 class="card-title mb-0">分類別支出</h5>
        </div>
        <div class="card-body">
            <canvas id="categoryExpenseChart"
                data-expenses='@json($categoryExpenses)'></canvas>
        </div>
    </div>
</div>
```

#### JavaScript（既存の `<script>` ブロックに追記）
```javascript
document.addEventListener('DOMContentLoaded', function () {
    const canvas = document.getElementById('categoryExpenseChart');
    const expenses = JSON.parse(canvas.dataset.expenses);

    // 上位7件 + その他
    const TOP_N = 7;
    const top = expenses.slice(0, TOP_N);
    const others = expenses.slice(TOP_N);
    const othersTotal = others.reduce(function (sum, e) { return sum + parseFloat(e.total); }, 0);

    const labels = top.map(function (e) { return e.category_name; });
    const data = top.map(function (e) { return parseFloat(e.total); });

    if (othersTotal > 0) {
        labels.push('その他');
        data.push(othersTotal);
    }

    new Chart(canvas, {
        type: 'doughnut',
        data: {
            labels: labels,
            datasets: [{
                data: data,
                borderWidth: 1,
            }]
        },
        options: {
            responsive: true,
            plugins: {
                legend: { position: 'bottom' },
                tooltip: {
                    callbacks: {
                        label: function (context) {
                            const value = context.parsed;
                            return context.label + ': ' + value.toLocaleString() + '円';
                        }
                    }
                }
            }
        }
    });
});
```

---

## エッジケース

| ケース | 対応 |
|--------|------|
| カテゴリが7件以下 | `others` が空 → 「その他」スライスを追加しない |
| 支出が0件 | `expenses` が空配列 → Chart.js は空データでも描画エラーにならない |
| カテゴリ名が未設定 | PHP側で `'未分類'` にフォールバック済み |

---

## スコープ外

- 右カラム（予算 vs 実支出）は変更しない
- 色のカスタマイズは行わない（Chart.js デフォルトカラーを使用）
- グラフクリックによる詳細遷移は実装しない
