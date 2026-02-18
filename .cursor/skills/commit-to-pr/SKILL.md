---
name: commit-to-pr
description: Runs the workflow from committing changes to creating a PR (main に戻る → pull → ブランチを切る → コミット → プッシュ → PR作成). Use when the user asks to commit and create a PR, to "ブランチを切ってコミットしてプッシュしてPRを作成", or to run the full "コミットからPR作成" flow.
---

# コミットから PR 作成までの流れ

ユーザーが「main に戻って pull してブランチを切って修正をコミットしてプッシュして PR を作成して」などと依頼したときに、この手順に沿って実行する。

## 前提

- リポジトリは Git 管理されており、`origin` が設定されている。
- プッシュ・PR 作成は **GitHub への書き込み** のため、ワークスペースルール（`.cursor/rules/00-core-safety.mdc`）に従い、**実行前に対象 Repo・ブランチ・実行内容を提示し、ユーザーの承認（OK / Yes / はい）を得てから実行する**。承認なしに push / `gh pr create` は行わない。

## ワークフロー

### 1. 状態確認

```bash
git status -sb
git branch --show-current
git diff --name-status
git diff --stat
```

- 別ブランチに未コミットの変更がある場合: 次で stash してから main に切り替える。
- すでに main にいる場合: stash は不要。そのまま pull。

### 2. main に戻って最新化

変更がある場合は stash（未追跡も含める）:

```bash
git stash push -u -m "WIP: <短い説明>"
```

main に切り替えて pull:

```bash
git checkout main
git pull origin main
```

### 3. ブランチを切る

ブランチ名は目的に合わせて `feature/xxx` または `fix/xxx` など。ユーザーが名前を指定していなければ、変更内容から推測して命名する。

```bash
git checkout -b feature/<目的の短い名前>
```

stash した変更を戻す:

```bash
git stash pop
```

### 4. コミット前の整理

- デバッグ用ログ（`#region agent log`、`.cursor/debug.log` 出力など）が含まれていれば削除する。
- 不要な変更が混ざっていないか確認する。

### 5. コミット

全変更をステージ:

```bash
git add <必要なファイル>
# または git add -A でまとめて（意図に合わせて選択）
```

コミットメッセージは 1 行目に要約（英語推奨、`feat:` / `fix:` など）、必要なら 2 行空けて本文。

例:

```
feat: local test data, dashboard local banner, pagination icon size

- LocalTestDataSeeder (local only), dashboard banner, transaction empty state
- Pagination SVG 12px, routes cleanup, README
```

```bash
git commit -m "<メッセージ>"
```

### 6. プッシュ（要・ユーザー承認）

**実行前に必ず以下を提示する:**

- 対象 Repo: 例 `kh55/kakeibo_app01`
- 対象ブランチ: 例 `feature/local-test-data-and-ux-improvements`
- 実行内容: `git push -u origin <ブランチ名>`

ユーザーが「はい」「OK」などで承認してから実行:

```bash
git push -u origin <ブランチ名>
```

### 7. PR 作成（要・ユーザー承認）

**実行前に必ず以下を提示する:**

- 対象 Repo
- 対象ブランチ（head）
- base: 通常 `main`
- 実行内容: `gh pr create` で PR 作成

PR 本文は `.cursor/rules/20-pr-create-from-diff.mdc` のテンプレに合わせる:

- **背景**: 変更の理由・課題
- **概要**: この PR で実現すること
- **実施内容**: ファイル／機能単位の変更点
- **参考**: 関連 Issue やドキュメント
- **手順**: 確認手順（任意）

差分の要約は次で取得して本文に反映する:

```bash
git diff origin/main --name-status
git diff origin/main --stat
```

ユーザーが承認してから実行:

```bash
gh pr create --base main --head <ブランチ名> --title "<タイトル>" --body "<本文>"
```

タイトルはコミットの要約と揃えるとよい（例: `feat: local test data, dashboard banner, pagination icon`）。

## チェックリスト（実行前）

- [ ] デバッグ用・一時的なコードを削除したか
- [ ] コミットメッセージが変更内容を表しているか
- [ ] プッシュ・PR 作成の前にユーザー承認を取ったか
- [ ] PR 本文に機密情報やトークンを含めていないか

## 使用例（ユーザー発話）

- 「main に戻って pull して、ブランチ切って、修正コミットしてプッシュして PR 作って」
- 「コミットから PR 作成までの流れを実行して」
- 「いまの変更でブランチ切ってコミットして PR 出して」

いずれも上記ワークフローに沿って進め、push と `gh pr create` の前に対象 Repo・ブランチ・実行内容を提示し、ユーザー承認を得る。

## 参照

- PR 本文テンプレ・差分の取り方: [.cursor/rules/20-pr-create-from-diff.mdc](../../rules/20-pr-create-from-diff.mdc)
- GitHub 書き込みの安全ルール: [.cursor/rules/00-core-safety.mdc](../../rules/00-core-safety.mdc)
