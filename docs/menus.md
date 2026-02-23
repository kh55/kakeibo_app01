# メニュー一覧と説明

家計簿アプリのナビゲーションメニュー（`resources/views/layouts/navigation.blade.php`）に表示される各メニューの説明と、機能の有無の調査結果をまとめています。

---

## メニュー構成

| 表示名 | 種別 | ルート名 | パス／備考 |
|--------|------|----------|------------|
| 家計簿アプリ | ブランドリンク | `dashboard` | `/dashboard` |
| ダッシュボード | リンク | `dashboard` | `/dashboard` |
| 取引明細 | リンク | `transactions.index` | `/transactions` |
| 予算 | リンク | `budgets.index` | `/budgets` |
| 定期支出 | リンク | `recurring-rules.index` | `/recurring-rules` |
| 分割払い | リンク | `installment-plans.index` | `/installment-plans` |
| 予定表 | リンク | `cashflow.index` | `/cashflow` |
| 年間収支 | リンク | `annual-summary.index` | `/annual-summary` |
| マスタ | ドロップダウン | - | 下位に「支払手段」「分類」 |
| 　└ 支払手段 | サブメニュー | `accounts.index` | `/accounts` |
| 　└ 分類 | サブメニュー | `categories.index` | `/categories` |
| インポート/エクスポート | リンク | `import-export.index` | `/import-export` |
| （ユーザー名） | ドロップダウン | - | 下位にプロフィール・ログインログ・ログアウト |
| 　└ プロフィール | サブメニュー | `profile.edit` | `/profile` |
| 　└ ログインログ | サブメニュー | `profile.login-logs` | `/profile/login-logs` |
| 　└ ログアウト | フォーム送信 | `logout` | POST `/logout`（auth） |

---

## 各メニューの説明

### 1. ダッシュボード

- **説明**: 月別の収支サマリ、カテゴリ別支出、予算との比較、今後の支払予定を表示するトップ画面。
- **ルート**: `dashboard`（`GET /dashboard`）
- **コントローラー**: `App\Http\Controllers\DashboardController::index`
- **ビュー**: `dashboard.index`
- **状態**: ✅ 実装済み・動作

---

### 2. 取引明細

- **説明**: 収支の取引を一覧表示し、年月・種別（収入/支出）で絞り込み。新規登録・編集・削除が可能。
- **ルート**: `transactions.index`（Resource: `transactions`）
- **コントローラー**: `App\Http\Controllers\TransactionController`
- **ビュー**: `transactions.index`, `transactions.create`, `transactions.edit`
- **状態**: ✅ 実装済み・動作

---

### 3. 予算

- **説明**: 月別・カテゴリ別の予算を設定し、一覧で実績・残り・達成率を確認。同一年月・同一カテゴリの重複はバリデーションで防止。
- **ルート**: `budgets.index`（Resource: `budgets`）
- **コントローラー**: `App\Http\Controllers\BudgetController`
- **ビュー**: `budgets.index`, `budgets.create`, `budgets.edit`
- **状態**: ✅ 実装済み・動作

---

### 4. 定期支出

- **説明**: 毎月・毎年など繰り返し発生する支出ルールを登録。ルールから取引を一括生成する機能あり。
- **ルート**: `recurring-rules.index`（Resource: `recurring-rules`）、`recurring-rules.generate`（POST）
- **コントローラー**: `App\Http\Controllers\RecurringRuleController`
- **ビュー**: `recurring.index`, `recurring.create`, `recurring.edit`
- **状態**: ✅ 実装済み・動作

---

### 5. 分割払い

- **説明**: 分割払いプランを登録し、支払い実績を記録。残金・今後の支払予定の管理。
- **ルート**: `installment-plans.index`（Resource: `installment-plans`）、`installment-plans.record-payment`（POST）
- **コントローラー**: `App\Http\Controllers\InstallmentPlanController`
- **ビュー**: `installments.index`, `installments.create`, `installments.edit`
- **状態**: ✅ 実装済み・動作

---

### 6. 予定表

- **説明**: キャッシュフロー予定を日付範囲で表示。取引・定期支出・分割払いから算出した残高と予定の一覧。同期（sync）で再計算可能。
- **ルート**: `cashflow.index`（Resource: `cashflow`）、`cashflow.sync`（POST）
- **コントローラー**: `App\Http\Controllers\CashflowController`
- **ビュー**: `cashflow.index`, `cashflow.create`, `cashflow.edit`
- **状態**: ✅ 実装済み・動作

---

### 7. 年間収支

- **説明**: 年を指定すると、1月〜12月の月別の収入・支出・差額・繰越を一覧表示する収支表。
- **ルート**: `annual-summary.index`（`GET /annual-summary`）
- **コントローラー**: `App\Http\Controllers\DashboardController::annualSummary`
- **ビュー**: `dashboard.annual`
- **状態**: ✅ 実装済み・動作

---

### 8. マスタ

#### 8.1 支払手段

- **説明**: 現金・口座・カードなど支払手段（Account）のマスタ。取引・予定で選択するために利用。
- **ルート**: `accounts.index`（Resource: `accounts`）
- **コントローラー**: `App\Http\Controllers\AccountController`
- **ビュー**: `accounts.index`, `accounts.create`, `accounts.edit`
- **状態**: ✅ 実装済み・動作

#### 8.2 分類

- **説明**: 収入・支出の分類（Category）マスタ。取引・予算で選択するために利用。
- **ルート**: `categories.index`（Resource: `categories`）
- **コントローラー**: `App\Http\Controllers\CategoryController`
- **ビュー**: `categories.index`, `categories.create`, `categories.edit`
- **状態**: ✅ 実装済み・動作

---

### 9. インポート/エクスポート

- **説明**: CSV から取引をインポート、指定年月の取引を CSV でエクスポートする画面。
- **ルート**: `import-export.index`（GET `/import-export`）、`import-export.import`（POST）、`import-export.export`（GET）
- **コントローラー**: `App\Http\Controllers\ImportExportController`
- **ビュー**: `import-export.index`
- **状態**: ✅ 実装済み・動作

---

### 10. ユーザーメニュー（右端ドロップダウン）

#### 10.1 プロフィール

- **説明**: ユーザー名・メールアドレス等のプロフィール編集、パスワード変更、アカウント削除。
- **ルート**: `profile.edit`（GET/PATCH/DELETE `/profile`）
- **コントローラー**: `App\Http\Controllers\ProfileController`
- **ビュー**: `profile.edit`
- **状態**: ✅ 実装済み・動作

#### 10.2 ログインログ

- **説明**: 自分のログイン成功・失敗ログを一覧表示（同一 IP の失敗も表示）。セキュリティ監視用。
- **ルート**: `profile.login-logs`（GET `/profile/login-logs`）
- **コントローラー**: `App\Http\Controllers\ProfileController::loginLogs`
- **ビュー**: `profile.login-logs`
- **状態**: ✅ 実装済み・動作

#### 10.3 ログアウト

- **説明**: セッションを終了しログアウト。POST で `logout` ルートに送信。
- **ルート**: `logout`（`routes/auth.php`、POST）
- **コントローラー**: `App\Http\Controllers\Auth\AuthenticatedSessionController::destroy`
- **状態**: ✅ 実装済み・動作

---

## 調査結果：機能していないメニューについて

**結論: ナビに表示されているメニューは、いずれもルート・コントローラー・ビューが定義されており、コード上は「未実装」や「リンク切れ」のメニューはありません。**

実施した確認内容は以下のとおりです。

1. **ルート**: `routes/web.php` および `routes/auth.php` で、ナビで参照している全ルート名（`dashboard`, `annual-summary.index`, `transactions.index`, `budgets.index`, `recurring-rules.index`, `installment-plans.index`, `cashflow.index`, `accounts.index`, `categories.index`, `import-export.index`, `profile.edit`, `profile.login-logs`, `logout`）が定義されていることを確認。
2. **コントローラー**: 上記ルートに対応するコントローラーが存在し、必要なメソッド（`index`, `edit`, `create` 等）が実装されていることを確認。
3. **ビュー**: 各コントローラーが `return view(...)` で参照しているビューファイル（例: `dashboard.index`, `transactions.index`, `recurring.index`, `installments.index` など）が `resources/views/` 配下に存在することを確認。

そのため、**「実際には機能していないメニュー」は見つかっていません。** 運用中に 500 エラーや空白画面が出る場合は、環境（DB・キャッシュ・権限）や特定のデータに依存した処理の不具合として別途調査する必要があります。

---

## 参照

- ナビ定義: `resources/views/layouts/navigation.blade.php`
- 認証付きルート: `routes/web.php`
- 認証・ログアウト: `routes/auth.php`
