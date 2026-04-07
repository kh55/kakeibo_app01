# 固定予算設計書

**日付:** 2026-04-07
**ブランチ:** feature/fixed-budget

## 概要

現在の予算管理は年・月ごとに個別登録する方式。これを「カテゴリごとに1つの固定予算額を設定し、どの月でも共通で使う」方式に変更する。編集・削除は引き続き可能。

## 要件

- カテゴリごとに固定の月次予算額を1つ登録する
- 予算管理画面では月フィルタで「その月の実績 vs 固定予算」を比較できる
- ダッシュボードでは当月の予算比較を表示する（既存のセクションを維持）
- 編集・削除は可能

## データベース変更

### `budgets` テーブル

**変更前:**
```
budgets (id, user_id, year, month, category_id, amount, timestamps)
UNIQUE(user_id, year, month, category_id)
INDEX(user_id, year, month)
```

**変更後:**
```
budgets (id, user_id, category_id, amount, timestamps)
UNIQUE(user_id, category_id)
```

**マイグレーション手順:**
1. `budgets` テーブルの全データを削除（truncate）
2. `year`, `month` カラムを drop
3. ユニーク制約を `(user_id, year, month, category_id)` から `(user_id, category_id)` に変更
4. `INDEX(user_id, year, month)` を削除

## バックエンド変更

### `Budget` モデル

- `$fillable` から `year`, `month` を削除
- `$casts` から `year`, `month` を削除

### `BudgetController`

| メソッド | 変更内容 |
|---------|---------|
| `index` | `year`/`month` で予算を絞らない。実績計算は引き続き `year`/`month` で絞る |
| `create` | `$year`/`$month` をビューに渡さない |
| `store` | `year`/`month` バリデーション削除。重複チェックを `(user_id, category_id)` に変更 |
| `edit` | 変更なし（`budget` モデルに `year`/`month` がなくなるだけ） |
| `update` | `year`/`month` バリデーション削除。重複チェックを `(user_id, category_id)` に変更 |
| `destroy` | 変更なし |

### `DashboardService::getBudgetComparison`

- 予算クエリから `where('year', $year)->where('month', $month)` を削除
- 実績クエリは引き続き `forMonth($year, $month)` で絞る

## フロントエンド変更

### `budgets/index.blade.php`

- 年・月フィルタUIは残す（実績比較月の切り替え用）
- テーブル表示は変更なし（予算額・実績額・残り・達成率）

### `budgets/create.blade.php`

- 年・月の入力フィールドを削除

### `budgets/edit.blade.php`

- 年・月の入力フィールドを削除

### `dashboard/index.blade.php`

- 既存の「予算 vs 実支出」セクションはそのまま維持（データは固定予算から取得される）

## テスト観点

- 固定予算を登録できる
- 同一カテゴリへの重複登録がバリデーションで拒否される
- 月フィルタを変えると実績額が変わる（予算額は変わらない）
- 編集・削除が正常に動作する
- ダッシュボードで当月の予算比較が表示される
