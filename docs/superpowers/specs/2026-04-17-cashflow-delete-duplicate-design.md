# 予定表 削除・複製機能 設計書

**Date:** 2026-04-17

## 概要

予定表（キャッシュフロー）の一覧画面に、各レコードへの削除ボタンと複製ボタンを追加する。

## 要件

- 各行に「削除」「複製」ボタンを表示する
- 削除: 確認ダイアログ後に即削除
- 複製: 値を引き継ぎ日付+1ヶ月にした新規作成フォームを開く。保存するまでレコードは作成されない

## 実装方針

### 削除

- 既存の `destroy` アクション（`DELETE /cashflow/{id}`）を使用
- ボタン押下時に `confirm()` ダイアログで確認
- OKなら既存フォームをsubmit、キャンセルなら何もしない

### 複製

- 新規ルート `GET /cashflow/{cashflowEntry}/duplicate` を追加
- `CashflowController::duplicate()` メソッドを追加
  - 元レコードを取得し、日付を+1ヶ月にした値をビューに渡す
  - `cashflow.create` ビューに `$prefill` として渡す（既存フォームを再利用）
- `cashflow/create.blade.php` で `$prefill` があれば初期値をセットする
- 保存時は既存の `store` アクションを使用

## 変更ファイル

| ファイル | 変更内容 |
|---|---|
| `routes/web.php` | `duplicate` ルート追加 |
| `app/Http/Controllers/CashflowController.php` | `duplicate()` メソッド追加 |
| `resources/views/cashflow/index.blade.php` | 削除・複製ボタン追加 |
| `resources/views/cashflow/create.blade.php` | `$prefill` 対応 |

## ルート設計

```
GET  /cashflow/{cashflowEntry}/duplicate  → CashflowController@duplicate
```

既存ルートは変更なし。

## テスト方針

Feature テストで以下を確認:

1. `GET /cashflow/{id}/duplicate` が200を返す
2. フォームに元レコードの値が引き継がれている（日付は+1ヶ月）
3. `DELETE /cashflow/{id}` でレコードが削除される（既存テストで対応済みの場合はスキップ）
