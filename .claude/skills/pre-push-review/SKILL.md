---
name: pre-push-review
description: kakeibo_app01 プロジェクトで git push を実行する前にコードレビューを行うスキル。「pushして」「git pushする前にレビューして」「pushする」「コードをpushしたい」「リモートに送る前に確認して」など、git push に関連する発言のたびに必ず使うこと。テスト・スタイルチェック・コードレビューをセットで行い、問題があれば修正を提案する。
---

# Push 前コードレビュー

`git push` の前に、このスキルで必ず確認する。目的は「バグや問題をリモートに送る前に発見すること」。後で修正するより今直すほうがコストが低い。

## Step 1: 変更内容の把握

```bash
# pushする差分を確認（originとの差分）
git log origin/$(git branch --show-current)..HEAD --oneline 2>/dev/null || git log HEAD~3..HEAD --oneline

# 変更ファイルの差分
git diff origin/$(git branch --show-current)..HEAD 2>/dev/null || git diff HEAD~3..HEAD
```

まだリモートに存在しないブランチなら `git diff main..HEAD` を使う。

## Step 2: 自動チェック（PHPUnit + Pint）

```bash
# スタイルチェック（エラーのみ、自動修正しない）
docker compose exec -T app vendor/bin/pint --test

# テスト実行
docker compose exec -T app vendor/bin/phpunit
```

両方の結果を記録しておく。

## Step 3: コードレビュー

Step 1 で取得した差分を読んで、以下の観点でレビューする：

**確認ポイント**
- **バグ・ロジックエラー**: 条件分岐の漏れ、型の不一致、nullチェック忘れ
- **セキュリティ**: SQLインジェクション、XSS、認可チェック漏れ
- **Laravel規約**: サービスクラス・リポジトリの責務、Eloquentの使い方
- **CLAUDE.md 規約**: テストメソッド名（スネークケース）、`new Foo()` ではなく `new Foo`、コミットメッセージ形式
- **テストの質**: 追加したコードに対応するテストがあるか、テストが実態を反映しているか

差分が小さい場合でも丁寧に読む。コメントは具体的に（「line X の Y は Z のリスクがある」という形で）。

## Step 4: 結果をまとめる

以下の形式でレポートを出す：

```
## Push 前レビュー結果

### 自動チェック
- Pint: ✅ PASS / ❌ FAIL（エラー内容）
- PHPUnit: ✅ PASS / ❌ FAIL（失敗テスト名）

### コードレビュー
**ブロッキング（pushを止めるべき問題）**
- [具体的な問題と場所]

**警告（できれば直したほうがいい）**
- [具体的な問題と場所]

**情報（任意）**
- [軽微な改善提案]

### 総合判定
✅ push してOK / ⚠️ 修正推奨 / ❌ push 前に修正が必要
```

## Step 5: 修正の確認

問題が見つかった場合は、修正するかどうか聞く：

- **ブロッキング問題がある場合**: 「以下の問題を修正してから push することを推奨します。修正しますか？」
- **警告のみの場合**: 「警告はありますが push は可能です。修正してから push しますか？それともこのまま push しますか？」
- **問題なし**: 「問題は見つかりませんでした。push します。」

ユーザーが「修正して」と言ったら修正してから push する。
「このまま push して」と言ったら push を実行する。

## 注意

- Pint の失敗は `docker compose exec -T app vendor/bin/pint` で自動修正できる
- PHPUnit の失敗はコードを修正してから再テストが必要
- `git push` を実行するのは必ずユーザーの確認後
