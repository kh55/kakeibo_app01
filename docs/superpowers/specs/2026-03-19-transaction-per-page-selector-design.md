# 取引明細ページ 表示件数セレクター 設計書

**日付:** 2026-03-19
**ブランチ:** feature/transaction-per-page-selector

## 概要

取引明細ページ（`/transactions`）のページネーション表示件数をユーザーが選択できるようにする。選択肢は 20 / 50 / 100 件。設定は URLパラメータ（`?per_page=50`）で保持し、セッションやDBへの永続化は行わない。

## 変更対象

### 1. コントローラー（`app/Http/Controllers/TransactionController.php`）

`index` メソッドに以下を追加：

- `$request->get('per_page', 50)` で件数を取得
- 許可値 `[20, 50, 100]` に含まれない場合は 50 にフォールバック
- `paginate($perPage)` に渡す
- ビューへ `$perPage` を渡す

### 2. ビュー（`resources/views/transactions/index.blade.php`）

フィルターフォームに `<select name="per_page">` を追加：

- 選択肢: 20件 / 50件 / 100件
- 現在値（`$perPage`）を `selected` で反映
- 既存の `col-md-3` グリッド列に追加（必要に応じてレイアウト調整）

ページネーションリンクを以下に変更：

```php
{{ $transactions->appends(request()->query())->links() }}
```

これにより `per_page` を含む全クエリパラメータがページ移動時に引き継がれる。

## データフロー

```
ユーザーがセレクトで件数を選択
→ フィルタボタンで送信
→ GET /transactions?year=2026&month=3&type=&per_page=20
→ コントローラーが per_page を検証・取得
→ paginate(20) でクエリ実行
→ ビューに $perPage を渡して selected を反映
→ ページネーションリンクに全パラメータを引き継ぎ
```

## バリデーション

| 条件 | 動作 |
|------|------|
| `per_page` が `[20, 50, 100]` のいずれか | そのまま使用 |
| `per_page` が未指定 | デフォルト 50 |
| `per_page` が不正値（例: 999, abc） | 50 にフォールバック |

## 影響範囲

- 変更ファイル: 2ファイル（コントローラー、ビュー）
- 既存の年・月・種別フィルターの動作に影響なし
- DB変更なし、マイグレーションなし
