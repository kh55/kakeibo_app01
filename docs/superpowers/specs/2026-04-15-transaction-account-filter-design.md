# 取引明細ページ 支払手段フィルター追加

**Date:** 2026-04-15

## 概要

取引明細ページ（`/transactions`）のフィルターに「支払手段」セレクトボックスを追加する。カテゴリフィルターと同じパターンで実装し、「未選択のみ」への絞り込みもサポートする。

## 変更ファイル

- `app/Http/Controllers/TransactionController.php`
- `resources/views/transactions/index.blade.php`

## 詳細設計

### TransactionController::index()

1. `$accountId = $request->get('account_id')` でパラメータを取得する
2. クエリにフィルターを追加する：
   - `$accountId === 'null'` の場合 → `whereNull('account_id')`
   - `$accountId` が値を持つ場合 → `where('account_id', $accountId)`
3. `$filterAccounts = $user->accounts()->orderBy('sort_order')->get()` で全アカウントを取得する（`enabled` に関わらず全件。無効化済み口座の過去取引も絞り込めるようにするため）
4. ビューに `$accountId` と `$filterAccounts` を渡す

### transactions/index.blade.php

カテゴリセレクト（`name="category_id"`）の直後に `col-md-2` のカラムを追加する：

```html
<div class="col-md-2">
    <select name="account_id" class="form-select">
        <option value="">支払手段：すべて</option>
        <option value="null" {{ $accountId === 'null' ? 'selected' : '' }}>未選択</option>
        @foreach($filterAccounts as $account)
            <option value="{{ $account->id }}" {{ $accountId == $account->id ? 'selected' : '' }}>
                {{ $account->name }}
            </option>
        @endforeach
    </select>
</div>
```

## テスト方針

既存のカテゴリフィルターのテストパターンを参考に、以下をユニットテストで確認する：

- `account_id` を指定したとき、該当する取引のみ返ること
- `account_id=null` を指定したとき、`account_id` が NULL の取引のみ返ること
- `account_id` を指定しないとき、全件返ること

## 除外スコープ

- 支払手段フィルターの URL パラメータのバリデーション（既存の category_id と同様、バリデーションなし）
- 支払手段フィルターの状態をセッションに保持する機能
