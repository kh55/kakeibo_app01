# 設計仕様：並び順設定画面

**日付:** 2026-04-01
**対象ブランチ:** fix/pint-not-operator-style

---

## 概要

取引登録画面（支払手段・分類のドロップダウン）の並び順を、ユーザーが設定で切り替えられるようにする。

- **手動並び替え**（デフォルト）: 既存の `sort_order` カラムによる順序
- **利用頻度順**: 直近3ヶ月の取引数が多い順

---

## 1. データ層

### マイグレーション

`users` テーブルに `sort_preference` カラムを追加する。

| カラム名 | 型 | デフォルト | 説明 |
|---|---|---|---|
| `sort_preference` | `enum('manual', 'frequency')` | `'manual'` | 並び順の設定値 |

### User モデル

- `$fillable` に `sort_preference` を追加
- `$casts` への追加は不要（文字列のまま使用）

---

## 2. 設定画面

### ルート

```
GET  /settings    SettingsController@edit    settings.edit
PUT  /settings    SettingsController@update  settings.update
```

### SettingsController

- `edit`: ログインユーザーの `sort_preference` をビューに渡す
- `update`: `sort_preference`（`manual` または `frequency`）をバリデーションして保存し、`settings.edit` にリダイレクト

### ビュー: `resources/views/settings/edit.blade.php`

ラジオボタン2択のシンプルなフォーム。

```
並び順設定
  ○ 手動並び替え（デフォルト）
  ● 利用頻度順（直近3ヶ月）
```

### ナビゲーション

既存のナビゲーションバー（プロフィールリンクの近く）に「設定」リンクを追加。

---

## 3. 並び順ロジック

### TransactionController（`create` / `edit` アクション）

`$user->sort_preference` の値に応じてクエリを切り替える。

**`'manual'` の場合（既存と同じ）**
```php
$accounts = $user->accounts()->where('enabled', true)->orderBy('sort_order')->get();
$categories = $user->categories()->orderBy('sort_order')->get();
```

**`'frequency'` の場合**
```php
$accounts = $user->accounts()
    ->where('enabled', true)
    ->withCount(['transactions' => fn($q) => $q->where('date', '>=', now()->subMonths(3))])
    ->orderBy('transactions_count', 'desc')
    ->orderBy('sort_order')
    ->get();

$categories = $user->categories()
    ->withCount(['transactions' => fn($q) => $q->where('date', '>=', now()->subMonths(3))])
    ->orderBy('transactions_count', 'desc')
    ->orderBy('sort_order')
    ->get();
```

同頻度（0回含む）の場合は `sort_order` をタイブレーカーとして使用する。

---

## 4. テスト

### SettingsController テスト

- 設定画面（GET /settings）が正常に表示されること
- `sort_preference` を `frequency` に更新できること
- `sort_preference` を `manual` に戻せること
- 無効な値（`invalid` など）はバリデーションエラーになること

### TransactionController テスト

- `sort_preference = 'manual'` のとき、`sort_order` 順で accounts/categories が返ること
- `sort_preference = 'frequency'` のとき、直近3ヶ月の利用頻度が高い順で返ること
- 既存の `manual`（デフォルト）を前提としたテストは変更不要

---

## 影響範囲

| ファイル | 変更内容 |
|---|---|
| 新規マイグレーション | `sort_preference` カラム追加 |
| `app/Models/User.php` | `$fillable` に追加 |
| `app/Http/Controllers/SettingsController.php` | 新規作成 |
| `resources/views/settings/edit.blade.php` | 新規作成 |
| `routes/web.php` | `/settings` ルート追加 |
| `app/Http/Controllers/TransactionController.php` | `create`/`edit` の並び順ロジック変更 |
| ナビゲーションビュー | 「設定」リンク追加 |
| テストファイル | SettingsController テスト新規、TransactionController テスト追加 |
