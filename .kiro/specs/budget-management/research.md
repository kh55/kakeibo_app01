# Research & Design Decisions

---
**Purpose**: 予算管理機能の完成に必要な拡張の調査結果と設計判断の根拠を記録する。
---

## Summary

- **Feature**: budget-management
- **Discovery Scope**: Extension（既存予算CRUDへの進捗表示・重複防止の追加）
- **Key Findings**:
  - `budgets` テーブルに既に `unique(['user_id','year','month','category_id'])` が存在する。要件5はDB制約で担保可能。アプリ層でのバリデーションで友好的なエラーメッセージを返す。
  - 実績算出は `Transaction::where('user_id', $user->id)->forMonth($year, $month)->expense()->selectRaw('category_id, sum(amount) as total')->groupBy('category_id')` で取得可能。既存の `scopeForMonth`・`scopeExpense` を利用する。
  - 進捗表示は Controller で集計して View に渡す形で十分。複数画面で再利用する場合は Service に切り出し可能。
---

## Research Log

### 既存スキーマと制約

- **Context**: 要件5（同一年月・カテゴリの重複防止）の実装方針を決めるため。
- **Sources**: `database/migrations/2025_12_21_013310_create_budgets_table.php`
- **Findings**:
  - `budgets` に `$table->unique(['user_id', 'year', 'month', 'category_id'])` および `$table->index(['user_id', 'year', 'month'])` が定義済み。
  - 重複挿入・更新はDBで拒否される。アプリ層では `unique` バリデーションまたは try-catch で 409/422 とメッセージを返す設計が可能。
- **Implications**: 重複時は「この年月・カテゴリの予算は既に登録されています」等のメッセージを返す。Laravel の `unique` ルール（store: 除外なし、update: 当該レコード除外）で実装可能。

### 取引の実績集計

- **Context**: 要件3（予算に対する進捗表示）の実績額の算出方法。
- **Sources**: `app/Models/Transaction.php`, `app/Services/DashboardService.php`
- **Findings**:
  - `Transaction::scopeForMonth($year, $month)`, `scopeExpense()` が既に存在する。
  - `DashboardService` で `forMonth`・`where('type','expense')`・`sum('amount')` の利用実績あり。
  - カテゴリ別集計は `Transaction::where(...)->forMonth(...)->expense()->selectRaw('category_id, sum(amount) as total')->groupBy('category_id')->pluck('total','category_id')` で取得可能。
- **Implications**: 新規ライブラリ不要。Controller または専用 Service のいずれかで集計し、一覧用データにマージする。

### 既存パターンとの整合

- **Context**: Steering の「Policy で認可」「user_id スコープ」「リソース別 views」に合わせる。
- **Sources**: `.kiro/steering/structure.md`, `BudgetController`, `BudgetPolicy`
- **Findings**:
  - 編集・削除は既に `authorize('update'|'delete', $budget)` で Policy 参照。
  - 一覧は `Budget::where('user_id', $user->id)->where('year',...)->where('month',...)` でスコープ済み。
  - ビューは `resources/views/budgets/index.blade.php` にテーブルあり。列の追加のみで対応可能。
- **Implications**: 新規 Policy メソッドは不要。Controller の index で実績データを取得し、View に渡す。View では実績・残り・超過の有無を表示する。

---

## Architecture Pattern Evaluation

| Option | Description | Strengths | Risks / Limitations | Notes |
|--------|-------------|-----------|---------------------|-------|
| Controller 内集計 | index 内で Transaction を集計し View に渡す | 変更箇所が少ない、既存パターンと一致 | 他画面で実績が必要になった場合に重複 | 現状は一画面のみなので採用 |
| BudgetService 新設 | 実績取得を Service に集約 | 再利用・テストしやすい | 本機能のみでは過剰な可能性 | 将来ダッシュボード等で使うなら検討 |

**採用**: Controller 内で実績を集計し View に渡す。必要になったら Service に抽出する。

---

## Design Decisions

### Decision: 実績データの取得場所

- **Context**: 要件3を満たす「指定年月・カテゴリの支出合計」をどこで計算するか。
- **Alternatives Considered**:
  1. BudgetController::index 内で Transaction を集計し、budgets コレクションに実績をマージして View に渡す。
  2. BudgetService を新設し、getActualsByCategory(userId, year, month) を提供し Controller が呼ぶ。
- **Selected Approach**: Controller 内で集計。Transaction::where(...)->forMonth(...)->expense()->selectRaw('category_id, sum(amount) as total')->groupBy('category_id')->pluck('total','category_id') で category_id => 合計 を取得し、各 budget に実績・残り・超過フラグを付与して View に渡す。
- **Rationale**: 実績表示は現状予算一覧のみ。Steering の「複雑なロジックは Service」に照らし、単一クエリの集計は Controller で許容。ダッシュボード等で再利用する段階で Service に移す。
- **Trade-offs**: テストでは Controller の Feature テストで実績表示を検証する。Unit で集計ロジックだけを切り出す場合は Service 化と合わせて行う。
- **Follow-up**: 実装時に N+1 にならないよう、集計は 1 クエリに留める。

### Decision: 重複防止のエラー応答

- **Context**: 要件5の「同一 user_id・year・month・category_id が既に存在する場合は拒否」。
- **Alternatives Considered**:
  1. DB の unique 制約に任せ、IntegrityException を catch して 422 とメッセージを返す。
  2. store/update 前に exists チェックし、存在すれば 422 で返す（unique ルールまたは手動クエリ）。
- **Selected Approach**: バリデーションで `unique:budgets,year,month,category_id` を user_id スコープ付きで使用。update 時は当該 budget の id を除外。DB 制約は二重の担保として残す。
- **Rationale**: ユーザーに「この年月・このカテゴリの予算は既に登録されています」と明確に伝えられる。Laravel の標準バリデーションで一貫したエラー表示が可能。
- **Trade-offs**: バリデーションと DB 制約の二重チェックになるが、競合ウィンドウはごく小さいため許容する。
- **Follow-up**: Rule::unique の where で user_id を必ずスコープする。

---

## Risks & Mitigations

- **実績集計のパフォーマンス**: 月・ユーザー単位の 1 クエリで済むため、現規模では問題にならない。将来予算行が極端に増えた場合はインデックス（既存の user_id, date, type）で十分。→ 特段の対策は不要。
- **category_id が null の取引**: Transaction の category_id は nullable。実績は category_id で groupBy するため、null は「未分類」として一括される。要件上「カテゴリ別」との対応で、未分類は 0 または別表示の仕様でよい。→ 実装時に View で「未分類」の扱いを決める。
- **重複バリデーションと並行登録**: 同時に同一年月・カテゴリで store した場合、片方が DB 制約で失敗する。catch して 422 と同一メッセージを返すフォールバックを用意する。→ Error Handling に記載。

---

## References

- Laravel Validation: unique rule with additional where (e.g. user_id)
- 既存: `app/Models/Transaction.php` (scopeForMonth, scopeExpense)
- 既存: `database/migrations/2025_12_21_013310_create_budgets_table.php` (unique, index)
