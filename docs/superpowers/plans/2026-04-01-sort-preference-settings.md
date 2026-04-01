# Sort Preference Settings Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** ユーザーが取引登録フォームの支払手段・分類ドロップダウンの並び順を「手動」または「利用頻度順（直近3ヶ月）」に切り替えられる設定画面を追加する。

**Architecture:** `users.sort_preference` カラムに設定値を保存し、`SettingsController` が新規 `/settings` 画面を提供する。`TransactionController` の `create`/`edit` が設定値を読み取ってクエリの並び順を切り替える。

**Tech Stack:** Laravel 11, Blade, Bootstrap 5, PHPUnit (Feature tests)

---

## ブランチ作成

作業は **新規ブランチ** で行うこと。

```bash
git checkout main
git pull origin main
git checkout -b feature/sort-preference-settings
```

---

## ファイルマップ

| 操作 | ファイル | 内容 |
|---|---|---|
| 新規作成 | `database/migrations/YYYY_MM_DD_000000_add_sort_preference_to_users_table.php` | `sort_preference` カラム追加 |
| 修正 | `app/Models/User.php` | `$fillable` に追加 |
| 新規作成 | `app/Http/Controllers/SettingsController.php` | 設定画面コントローラー |
| 修正 | `routes/web.php` | `/settings` ルート追加 |
| 新規作成 | `resources/views/settings/edit.blade.php` | 設定画面ビュー |
| 修正 | `resources/views/layouts/navigation.blade.php` | ナビに「設定」リンク追加 |
| 修正 | `app/Http/Controllers/TransactionController.php` | 並び順ロジック切り替え |
| 新規作成 | `tests/Feature/SettingsControllerTest.php` | 設定画面のテスト |
| 新規作成 | `tests/Feature/TransactionSortPreferenceTest.php` | 並び順ロジックのテスト |

---

## Task 1: マイグレーション — `sort_preference` カラム追加

**Files:**
- Create: `database/migrations/YYYY_MM_DD_000000_add_sort_preference_to_users_table.php`（artisan で生成）
- Modify: `app/Models/User.php`

- [ ] **Step 1: テストを書く（SettingsControllerTest の雛形）**

`tests/Feature/SettingsControllerTest.php` を新規作成する。まずマイグレーション確認テストとして `sort_preference` デフォルト値をアサートするテストを書く。

```php
<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SettingsControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_has_default_sort_preference_of_manual(): void
    {
        $user = User::factory()->create();

        $this->assertEquals('manual', $user->sort_preference);
    }
}
```

- [ ] **Step 2: テストが失敗することを確認**

```bash
php artisan test tests/Feature/SettingsControllerTest.php --filter test_user_has_default_sort_preference_of_manual
```

Expected: FAIL（カラムが存在しないため）

- [ ] **Step 3: マイグレーションを生成・実装する**

```bash
php artisan make:migration add_sort_preference_to_users_table
```

生成されたファイルを開き、内容を以下に書き換える：

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->enum('sort_preference', ['manual', 'frequency'])->default('manual')->after('remember_token');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('sort_preference');
        });
    }
};
```

- [ ] **Step 4: `User` モデルの `$fillable` に追加する**

`app/Models/User.php` の `$fillable` 配列に `'sort_preference'` を追加する：

```php
protected $fillable = [
    'name',
    'email',
    'password',
    'sort_preference',
];
```

- [ ] **Step 5: マイグレーション実行**

```bash
php artisan migrate
```

- [ ] **Step 6: テストが通ることを確認**

```bash
php artisan test tests/Feature/SettingsControllerTest.php --filter test_user_has_default_sort_preference_of_manual
```

Expected: PASS

- [ ] **Step 7: コミット**

```bash
git add database/migrations/ app/Models/User.php tests/Feature/SettingsControllerTest.php
git commit -m "feat: add sort_preference column to users table"
```

---

## Task 2: SettingsController — 設定画面の表示・更新

**Files:**
- Create: `app/Http/Controllers/SettingsController.php`
- Modify: `routes/web.php`

- [ ] **Step 1: テストを追加する**

`tests/Feature/SettingsControllerTest.php` に以下のテストを追加する：

```php
public function test_settings_page_is_displayed_for_authenticated_user(): void
{
    $user = User::factory()->create();

    $response = $this->actingAs($user)->get(route('settings.edit'));

    $response->assertOk();
}

public function test_settings_page_redirects_unauthenticated_user(): void
{
    $response = $this->get(route('settings.edit'));

    $response->assertRedirect(route('login'));
}

public function test_sort_preference_can_be_updated_to_frequency(): void
{
    $user = User::factory()->create(['sort_preference' => 'manual']);

    $response = $this->actingAs($user)->put(route('settings.update'), [
        'sort_preference' => 'frequency',
    ]);

    $response->assertRedirect(route('settings.edit'));
    $this->assertEquals('frequency', $user->fresh()->sort_preference);
}

public function test_sort_preference_can_be_updated_to_manual(): void
{
    $user = User::factory()->create(['sort_preference' => 'frequency']);

    $response = $this->actingAs($user)->put(route('settings.update'), [
        'sort_preference' => 'manual',
    ]);

    $response->assertRedirect(route('settings.edit'));
    $this->assertEquals('manual', $user->fresh()->sort_preference);
}

public function test_invalid_sort_preference_value_fails_validation(): void
{
    $user = User::factory()->create();

    $response = $this->actingAs($user)->put(route('settings.update'), [
        'sort_preference' => 'invalid',
    ]);

    $response->assertSessionHasErrors('sort_preference');
}

public function test_settings_update_only_affects_authenticated_user(): void
{
    $user = User::factory()->create(['sort_preference' => 'manual']);
    $otherUser = User::factory()->create(['sort_preference' => 'manual']);

    $this->actingAs($user)->put(route('settings.update'), [
        'sort_preference' => 'frequency',
    ]);

    $this->assertEquals('manual', $otherUser->fresh()->sort_preference);
}
```

- [ ] **Step 2: テストが失敗することを確認**

```bash
php artisan test tests/Feature/SettingsControllerTest.php
```

Expected: FAIL（ルートが存在しないため）

- [ ] **Step 3: SettingsController を作成する**

`app/Http/Controllers/SettingsController.php` を新規作成する：

```php
<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SettingsController extends Controller
{
    public function edit(Request $request): View
    {
        return view('settings.edit', [
            'user' => $request->user(),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'sort_preference' => 'required|in:manual,frequency',
        ]);

        $request->user()->update($validated);

        return redirect()->route('settings.edit')->with('status', 'settings-updated');
    }
}
```

- [ ] **Step 4: ルートを追加する**

`routes/web.php` の `auth` ミドルウェアグループ内、プロフィールルートの下に追加する：

```php
// 設定
Route::get('/settings', [\App\Http\Controllers\SettingsController::class, 'edit'])->name('settings.edit');
Route::put('/settings', [\App\Http\Controllers\SettingsController::class, 'update'])->name('settings.update');
```

- [ ] **Step 5: テストが通ることを確認**

```bash
php artisan test tests/Feature/SettingsControllerTest.php
```

Expected: 1件失敗（ビューが存在しないため `settings_page_is_displayed` のみ）、残りはPASS。
※ビューは次のタスクで作成するため、この時点では `settings_page_is_displayed` は失敗のままでよい。

- [ ] **Step 6: コミット**

```bash
git add app/Http/Controllers/SettingsController.php routes/web.php tests/Feature/SettingsControllerTest.php
git commit -m "feat: add SettingsController and routes"
```

---

## Task 3: 設定画面ビューの作成

**Files:**
- Create: `resources/views/settings/edit.blade.php`

- [ ] **Step 1: ビューを作成する**

`resources/views/settings/` ディレクトリを作成し、`edit.blade.php` を新規作成する：

```blade
<x-app-layout>
    <x-slot name="header">
        <h2 class="h4">設定</h2>
    </x-slot>

    <div class="card">
        <div class="card-body">
            @if (session('status') === 'settings-updated')
                <div class="alert alert-success" role="alert">
                    設定を保存しました。
                </div>
            @endif

            <form method="POST" action="{{ route('settings.update') }}">
                @csrf
                @method('PUT')

                <h5 class="mb-3">並び順設定</h5>
                <p class="text-muted mb-3">取引登録画面の支払手段・分類ドロップダウンの並び順を設定します。</p>

                <div class="mb-3">
                    <div class="form-check mb-2">
                        <input class="form-check-input" type="radio" name="sort_preference" id="sort_manual"
                            value="manual" {{ $user->sort_preference === 'manual' ? 'checked' : '' }}>
                        <label class="form-check-label" for="sort_manual">
                            手動並び替え（デフォルト）
                        </label>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="radio" name="sort_preference" id="sort_frequency"
                            value="frequency" {{ $user->sort_preference === 'frequency' ? 'checked' : '' }}>
                        <label class="form-check-label" for="sort_frequency">
                            利用頻度順（直近3ヶ月）
                        </label>
                    </div>
                    @error('sort_preference')
                        <div class="text-danger mt-1">{{ $message }}</div>
                    @enderror
                </div>

                <button type="submit" class="btn btn-primary">保存</button>
            </form>
        </div>
    </div>
</x-app-layout>
```

- [ ] **Step 2: テストが全て通ることを確認**

```bash
php artisan test tests/Feature/SettingsControllerTest.php
```

Expected: 全テスト PASS

- [ ] **Step 3: コミット**

```bash
git add resources/views/settings/edit.blade.php
git commit -m "feat: add settings view"
```

---

## Task 4: ナビゲーションに「設定」リンクを追加

**Files:**
- Modify: `resources/views/layouts/navigation.blade.php`

- [ ] **Step 1: ナビゲーションを修正する**

`resources/views/layouts/navigation.blade.php` の右側ユーザードロップダウン内、`ログインログ` の下・`<hr>` の上に「設定」リンクを追加する：

```blade
<li><a class="dropdown-item" href="{{ route('profile.edit') }}">プロフィール</a></li>
<li><a class="dropdown-item" href="{{ route('profile.login-logs') }}">ログインログ</a></li>
<li><a class="dropdown-item" href="{{ route('settings.edit') }}">設定</a></li>
<li><hr class="dropdown-divider"></li>
```

- [ ] **Step 2: 全テストが通ることを確認**

```bash
php artisan test
```

Expected: 全テスト PASS

- [ ] **Step 3: コミット**

```bash
git add resources/views/layouts/navigation.blade.php
git commit -m "feat: add settings link to navigation dropdown"
```

---

## Task 5: TransactionController — 並び順ロジックの切り替え

**Files:**
- Modify: `app/Http/Controllers/TransactionController.php`
- Create: `tests/Feature/TransactionSortPreferenceTest.php`

- [ ] **Step 1: テストを書く**

`tests/Feature/TransactionSortPreferenceTest.php` を新規作成する：

```php
<?php

namespace Tests\Feature;

use App\Models\Account;
use App\Models\Category;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TransactionSortPreferenceTest extends TestCase
{
    use RefreshDatabase;

    public function test_create_form_sorts_accounts_by_sort_order_when_preference_is_manual(): void
    {
        $user = User::factory()->create(['sort_preference' => 'manual']);
        $accountB = Account::factory()->create(['user_id' => $user->id, 'sort_order' => 1, 'enabled' => true]);
        $accountA = Account::factory()->create(['user_id' => $user->id, 'sort_order' => 0, 'enabled' => true]);

        $response = $this->actingAs($user)->get(route('transactions.create'));

        $response->assertOk();
        $accounts = $response->viewData('accounts');
        $this->assertEquals($accountA->id, $accounts->first()->id);
        $this->assertEquals($accountB->id, $accounts->last()->id);
    }

    public function test_create_form_sorts_categories_by_sort_order_when_preference_is_manual(): void
    {
        $user = User::factory()->create(['sort_preference' => 'manual']);
        $categoryB = Category::factory()->create(['user_id' => $user->id, 'sort_order' => 1]);
        $categoryA = Category::factory()->create(['user_id' => $user->id, 'sort_order' => 0]);

        $response = $this->actingAs($user)->get(route('transactions.create'));

        $response->assertOk();
        $categories = $response->viewData('categories');
        $this->assertEquals($categoryA->id, $categories->first()->id);
        $this->assertEquals($categoryB->id, $categories->last()->id);
    }

    public function test_create_form_sorts_accounts_by_frequency_when_preference_is_frequency(): void
    {
        $user = User::factory()->create(['sort_preference' => 'frequency']);
        $accountLow = Account::factory()->create(['user_id' => $user->id, 'sort_order' => 0, 'enabled' => true]);
        $accountHigh = Account::factory()->create(['user_id' => $user->id, 'sort_order' => 1, 'enabled' => true]);

        // accountHigh を直近3ヶ月に2回使用
        Transaction::factory()->count(2)->create([
            'user_id' => $user->id,
            'account_id' => $accountHigh->id,
            'date' => now()->subWeek(),
        ]);

        $response = $this->actingAs($user)->get(route('transactions.create'));

        $response->assertOk();
        $accounts = $response->viewData('accounts');
        $this->assertEquals($accountHigh->id, $accounts->first()->id);
        $this->assertEquals($accountLow->id, $accounts->last()->id);
    }

    public function test_create_form_sorts_categories_by_frequency_when_preference_is_frequency(): void
    {
        $user = User::factory()->create(['sort_preference' => 'frequency']);
        $categoryLow = Category::factory()->create(['user_id' => $user->id, 'sort_order' => 0]);
        $categoryHigh = Category::factory()->create(['user_id' => $user->id, 'sort_order' => 1]);

        // categoryHigh を直近3ヶ月に3回使用
        Transaction::factory()->count(3)->create([
            'user_id' => $user->id,
            'category_id' => $categoryHigh->id,
            'date' => now()->subWeek(),
        ]);

        $response = $this->actingAs($user)->get(route('transactions.create'));

        $response->assertOk();
        $categories = $response->viewData('categories');
        $this->assertEquals($categoryHigh->id, $categories->first()->id);
        $this->assertEquals($categoryLow->id, $categories->last()->id);
    }

    public function test_frequency_sort_ignores_transactions_older_than_3_months(): void
    {
        $user = User::factory()->create(['sort_preference' => 'frequency']);
        $accountOld = Account::factory()->create(['user_id' => $user->id, 'sort_order' => 0, 'enabled' => true]);
        $accountNew = Account::factory()->create(['user_id' => $user->id, 'sort_order' => 1, 'enabled' => true]);

        // accountOld は3ヶ月より前に多数使用（カウント対象外）
        Transaction::factory()->count(5)->create([
            'user_id' => $user->id,
            'account_id' => $accountOld->id,
            'date' => now()->subMonths(4),
        ]);
        // accountNew は直近3ヶ月に1回使用
        Transaction::factory()->create([
            'user_id' => $user->id,
            'account_id' => $accountNew->id,
            'date' => now()->subWeek(),
        ]);

        $response = $this->actingAs($user)->get(route('transactions.create'));

        $accounts = $response->viewData('accounts');
        $this->assertEquals($accountNew->id, $accounts->first()->id);
    }

    public function test_frequency_sort_falls_back_to_sort_order_when_no_recent_transactions(): void
    {
        $user = User::factory()->create(['sort_preference' => 'frequency']);
        $accountB = Account::factory()->create(['user_id' => $user->id, 'sort_order' => 1, 'enabled' => true]);
        $accountA = Account::factory()->create(['user_id' => $user->id, 'sort_order' => 0, 'enabled' => true]);

        $response = $this->actingAs($user)->get(route('transactions.create'));

        $accounts = $response->viewData('accounts');
        // 頻度が同じ（0件）なので sort_order が適用される
        $this->assertEquals($accountA->id, $accounts->first()->id);
    }
}
```

- [ ] **Step 2: Account と Category の Factory が存在するか確認し、なければ作成する**

```bash
php artisan make:factory AccountFactory --model=Account
php artisan make:factory CategoryFactory --model=Category
```

既に存在する場合はスキップ。存在する場合はファイルを確認して必要なフィールドが定義されているか確認する。

Account factory の例：
```php
public function definition(): array
{
    return [
        'user_id' => User::factory(),
        'name' => fake()->word(),
        'type' => 'card',
        'enabled' => true,
        'sort_order' => 0,
    ];
}
```

Category factory の例：
```php
public function definition(): array
{
    return [
        'user_id' => User::factory(),
        'name' => fake()->word(),
        'type' => 'expense',
        'color' => '#000000',
        'sort_order' => 0,
    ];
}
```

- [ ] **Step 3: テストが失敗することを確認**

```bash
php artisan test tests/Feature/TransactionSortPreferenceTest.php
```

Expected: FAIL（ロジックが未実装のため、frequency 時も sort_order 順になる）

- [ ] **Step 4: TransactionController を修正する**

`app/Http/Controllers/TransactionController.php` の `create()` メソッドを修正する：

```php
public function create()
{
    $user = Auth::user();

    if ($user->sort_preference === 'frequency') {
        $accounts = $user->accounts()
            ->where('enabled', true)
            ->withCount(['transactions as recent_count' => fn ($q) => $q->where('date', '>=', now()->subMonths(3))])
            ->orderBy('recent_count', 'desc')
            ->orderBy('sort_order')
            ->get();
        $categories = $user->categories()
            ->withCount(['transactions as recent_count' => fn ($q) => $q->where('date', '>=', now()->subMonths(3))])
            ->orderBy('recent_count', 'desc')
            ->orderBy('sort_order')
            ->get();
    } else {
        $accounts = $user->accounts()->where('enabled', true)->orderBy('sort_order')->get();
        $categories = $user->categories()->orderBy('sort_order')->get();
    }

    return view('transactions.create', compact('accounts', 'categories'));
}
```

同様に `edit()` メソッドも同じロジックに修正する：

```php
public function edit(Transaction $transaction)
{
    $this->authorize('update', $transaction);
    $user = Auth::user();

    if ($user->sort_preference === 'frequency') {
        $accounts = $user->accounts()
            ->where('enabled', true)
            ->withCount(['transactions as recent_count' => fn ($q) => $q->where('date', '>=', now()->subMonths(3))])
            ->orderBy('recent_count', 'desc')
            ->orderBy('sort_order')
            ->get();
        $categories = $user->categories()
            ->withCount(['transactions as recent_count' => fn ($q) => $q->where('date', '>=', now()->subMonths(3))])
            ->orderBy('recent_count', 'desc')
            ->orderBy('sort_order')
            ->get();
    } else {
        $accounts = $user->accounts()->where('enabled', true)->orderBy('sort_order')->get();
        $categories = $user->categories()->orderBy('sort_order')->get();
    }

    return view('transactions.edit', compact('transaction', 'accounts', 'categories'));
}
```

- [ ] **Step 5: テストが通ることを確認**

```bash
php artisan test tests/Feature/TransactionSortPreferenceTest.php
```

Expected: 全テスト PASS

- [ ] **Step 6: 全テストが通ることを確認**

```bash
php artisan test
```

Expected: 全テスト PASS（既存テストへの影響がないこと）

- [ ] **Step 7: コミット**

```bash
git add app/Http/Controllers/TransactionController.php tests/Feature/TransactionSortPreferenceTest.php
git commit -m "feat: sort accounts and categories by usage frequency when preference is set"
```

---

## 最終確認

- [ ] **全テスト実行**

```bash
php artisan test
```

Expected: 全テスト PASS

- [ ] **動作確認チェックリスト**（手動）
  - [ ] `/settings` にアクセスして設定画面が表示されること
  - [ ] 「利用頻度順」を選択して保存できること
  - [ ] 取引登録画面で支払手段・分類が頻度順で表示されること
  - [ ] 「手動並び替え」に戻すと元の `sort_order` 順に戻ること
  - [ ] ナビゲーションのユーザードロップダウンに「設定」リンクが表示されること
