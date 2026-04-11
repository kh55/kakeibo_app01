# 分類別支出ドーナツグラフ 実装プラン

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** ダッシュボードの「分類別支出トップ10」テーブルを、当月の分類別支出ドーナツグラフ（上位7件＋その他）に置き換える。

**Architecture:** `DashboardService::getCategoryExpenseSummary()` の limit を `null`（無制限）に変更して全カテゴリを取得し、Blade テンプレートの JS 側で上位7件＋その他に集計して Chart.js でドーナツグラフを描画する。

**Tech Stack:** PHP/Laravel, Blade, Chart.js（既存 CDN 導入済み）

---

## 変更ファイル一覧

| ファイル | 変更種別 | 内容 |
|----------|----------|------|
| `app/Services/DashboardService.php` | 修正 | `getCategoryExpenseSummary()` の `$limit` を `?int $limit = null` に変更し、`null` 時は `->limit()` を適用しない |
| `tests/Feature/DashboardServiceTest.php` | 修正 | `getCategoryExpenseSummary()` の無制限取得テストを追加 |
| `resources/views/dashboard/index.blade.php` | 修正 | トップ10テーブルをドーナツグラフに置き換え |

---

## Task 1: DashboardService の limit 引数を無制限対応に変更

**Files:**
- Modify: `app/Services/DashboardService.php`
- Test: `tests/Feature/DashboardServiceTest.php`

### TDD

- [ ] **Step 1: 失敗するテストを書く**

`tests/Feature/DashboardServiceTest.php` の末尾（クラス閉じ括弧の前）に追記する：

```php
public function test_get_category_expense_summary_returns_all_categories_when_no_limit(): void
{
    // 11カテゴリを作成し、それぞれ支出を1件ずつ登録する
    for ($i = 1; $i <= 11; $i++) {
        $category = \App\Models\Category::create([
            'user_id' => $this->user->id,
            'name' => 'カテゴリ' . $i,
            'type' => 'expense',
            'color' => '#000000',
            'sort_order' => $i,
        ]);
        Transaction::factory()->create([
            'user_id' => $this->user->id,
            'type' => 'expense',
            'amount' => $i * 1000,
            'category_id' => $category->id,
            'date' => '2025-04-10',
        ]);
    }

    $result = $this->service->getCategoryExpenseSummary($this->user, 2025, 4);

    // limit なしなので 11件すべて返る
    $this->assertCount(11, $result);
}

public function test_get_category_expense_summary_respects_explicit_limit(): void
{
    for ($i = 1; $i <= 11; $i++) {
        $category = \App\Models\Category::create([
            'user_id' => $this->user->id,
            'name' => 'カテゴリ' . $i,
            'type' => 'expense',
            'color' => '#000000',
            'sort_order' => $i,
        ]);
        Transaction::factory()->create([
            'user_id' => $this->user->id,
            'type' => 'expense',
            'amount' => $i * 1000,
            'category_id' => $category->id,
            'date' => '2025-04-10',
        ]);
    }

    $result = $this->service->getCategoryExpenseSummary($this->user, 2025, 4, 5);

    $this->assertCount(5, $result);
}
```

- [ ] **Step 2: テストを実行して失敗を確認する**

```bash
php artisan test --filter="test_get_category_expense_summary_returns_all_categories_when_no_limit|test_get_category_expense_summary_respects_explicit_limit"
```

期待結果: `FAIL` — 現在のデフォルトが `10` なので11件目が返らない

- [ ] **Step 3: DashboardService を修正する**

`app/Services/DashboardService.php` の `getCategoryExpenseSummary()` メソッドを以下に置き換える：

```php
/**
 * Get category-wise expense summary.
 */
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

- [ ] **Step 4: テストを実行してパスを確認する**

```bash
php artisan test --filter="test_get_category_expense_summary_returns_all_categories_when_no_limit|test_get_category_expense_summary_respects_explicit_limit"
```

期待結果: `PASS` 2件

- [ ] **Step 5: 既存テストも全部通ることを確認する**

```bash
php artisan test tests/Feature/DashboardServiceTest.php
```

期待結果: 全テスト `PASS`

- [ ] **Step 6: コミットする**

```bash
git add app/Services/DashboardService.php tests/Feature/DashboardServiceTest.php
git commit -m "feat: make getCategoryExpenseSummary limit optional (default unlimited)"
```

---

## Task 2: Blade テンプレートのテーブルをドーナツグラフに置き換え

**Files:**
- Modify: `resources/views/dashboard/index.blade.php`

### 実装

- [ ] **Step 1: 「分類別支出トップ10」カードのHTMLを置き換える**

`resources/views/dashboard/index.blade.php` の以下のブロックを探す：

```html
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">分類別支出トップ10</h5>
                </div>
                <div class="card-body">
                    <table class="table table-sm">
                        <thead>
                            <tr>
                                <th>分類</th>
                                <th class="text-end">金額</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($categoryExpenses as $expense)
                            <tr>
                                <td>{{ $expense['category_name'] }}</td>
                                <td class="text-end">{{ number_format($expense['total']) }}円</td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
```

以下に置き換える：

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

- [ ] **Step 2: ドーナツグラフの JavaScript を追加する**

既存の `</script>` タグ（月次推移グラフのスクリプトの閉じタグ）の直前に以下を追記する：

既存スクリプトの末尾はこうなっている：
```javascript
    });
    </script>
```

`});` の後に改行を入れて以下を追記し、最終的に以下の構造にする：

```javascript
    document.addEventListener('DOMContentLoaded', function () {
        const canvas = document.getElementById('categoryExpenseChart');
        if (!canvas) { return; }
        const expenses = JSON.parse(canvas.dataset.expenses);

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

- [ ] **Step 3: ブラウザで動作確認する**

ローカルサーバーが起動していない場合は起動する：

```bash
php artisan serve
```

ブラウザで `http://localhost:8000/dashboard` を開き、以下を確認する：

- 左下カードに「分類別支出」というタイトルのドーナツグラフが表示される
- 支出データがある場合、スライスが表示される（支出データがない場合は空グラフ）
- ホバー時にツールチップで「カテゴリ名: ○○円」と表示される
- 凡例がグラフ下部に表示される
- 右カード「予算 vs 実支出」は変わらず表示される

- [ ] **Step 4: コミットする**

```bash
git add resources/views/dashboard/index.blade.php
git commit -m "feat: replace category expense top-10 table with doughnut chart"
```

---

## 完了チェック

- [ ] `php artisan test tests/Feature/DashboardServiceTest.php` が全件 PASS
- [ ] ダッシュボードに「分類別支出」ドーナツグラフが表示される
- [ ] 8件以上カテゴリがある場合、「その他」スライスが表示される
- [ ] 7件以下の場合、「その他」スライスが表示されない
