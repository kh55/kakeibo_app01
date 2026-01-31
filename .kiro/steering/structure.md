# Project Structure

## Organization Philosophy

Laravel の標準構造に従い、ドメインごとに Controller / Model / Policy を対応させる。複雑なロジックは Service に集約（例: CashflowService, RecurringExpenseService）。

## Directory Patterns

### HTTP 層（`app/Http/`）

- **Controllers**: リソースコントローラーが中心。`TransactionController`, `BudgetController` など。Auth 関連は `Auth/` サブディレクトリ。
- **Requests**: Form Request は `Auth/` または直下（例: `ProfileUpdateRequest.php`）。バリデーションは Request に集約。

### ドメイン層（`app/`）

- **Models**: Eloquent モデル。`user_id` によるスコープが共通。必要に応じて `SoftDeletes`, ローカルスコープ（例: `forMonth`）を使用。
- **Policies**: 各リソースモデルに対応する Policy を登録。認可は Policy に一任。
- **Services**: 複数モデルや外部入出力をまたぐ処理（定期展開、キャッシュフロー同期、CSV 等）を Service に配置。

### コンソール（`app/Console/Commands/`）

- Artisan コマンド。例: `GenerateRecurringExpenses`（定期支出の自動展開）。

### ビュー（`resources/views/`）

- **layouts**: `app.blade.php`, `guest.blade.php`。`resources/views/layouts/`。
- **components**: 再利用 UI 部品（button, modal, input 等）は `resources/views/components/`。
- **リソース別**: `{resource}/index.blade.php`, `create.blade.php`, `edit.blade.php`。ルート名と対応（例: `transactions`, `budgets`, `recurring`）。

### ルート（`routes/`）

- **web.php**: 認証済みグループ内で `Route::resource(...)` を中心に定義。追加アクションは `Route::post(...)` で名前付き。
- **auth.php**: Breeze 由来の認証ルート。

## Naming Conventions

- **クラス・ファイル**: PascalCase（例: `TransactionController.php`, `RecurringRule.php`）。
- **ルート名**: ケバブケース、リソース名と一致（例: `transactions`, `recurring-rules`, `installment-plans`）。
- **Blade**: ケバブケース（例: `application-logo.blade.php`）。
- **DB テーブル**: スネークケース、複数形（Laravel 規約）。

## Import / Use Organization

```php
// 外部・フレームワーク → アプリの順が無難
use Illuminate\Http\Request;
use App\Models\Transaction;
use App\Http\Controllers\Controller;
```

- **PSR-4**: `App\` → `app/`, `Tests\` → `tests/`。Composer の autoload に準拠。

## Code Organization Principles

- 新規リソース追加時: Model → Migration → Policy 登録 → Controller（resource）→ routes → views の順で揃えると既存パターンと一致。
- ビジネスロジックが重い場合は Service を切り、Controller は薄く保つ。
- 認可は必ず Policy で行い、Controller では `authorize()` または `@can` を利用。

---
_Document patterns, not file trees. New files following patterns shouldn't require updates_
