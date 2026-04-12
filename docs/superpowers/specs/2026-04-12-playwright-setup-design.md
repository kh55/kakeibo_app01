# Playwright E2E セットアップ 設計ドキュメント

## 概要

Playwright を整備し、取引明細の分類フィルタをブラウザで自動テストする。push 前のチェックに組み込み、今後の E2E テストの基盤とする。

## アーキテクチャ

### テスト対象環境

- アプリ URL: `http://localhost:8080`（Docker Nginx）
- DB: `database/database.sqlite`（アプリ本体の SQLite）
- DB リセット: `php artisan migrate:fresh --seed --seeder=LocalTestDataSeeder` を Docker 経由で実行

### コンポーネント

**`playwright.config.ts`（ルート）**

- `baseURL: 'http://localhost:8080'`
- テスト対象: `tests/e2e/specs/**/*.spec.ts`
- ブラウザ: Chromium のみ
- `headless: true`（CI 想定）

**`tests/e2e/helpers/db.ts`**

- `resetDatabase()` 関数: `execSync('docker compose exec -T app php artisan migrate:fresh --seed --seeder=LocalTestDataSeeder')` を実行
- 既存の `auth.spec.ts` が `'../helpers/db'` からインポートしているため、このパスで作成

**`tests/e2e/specs/transaction-category-filter.spec.ts`**

- `beforeAll`: DB リセット
- `beforeEach`: `test@example.com / password` でログイン
- テストケース:
  1. カテゴリフィルタのドロップダウンが表示されていることを確認
  2. 「食費」を選択してフィルタ → 食費の取引のみ表示されることを確認
  3. 「未分類」を選択してフィルタ → 空状態メッセージが表示されることを確認（シーダーデータに未分類取引はない）

**`package.json`**

- `@playwright/test` を devDependencies に追加
- `test:e2e` スクリプトを追加: `npx playwright test`

### Pre-push への組み込み

**`.claude/skills/pre-push-review/SKILL.md`**

- Step 2 の自動チェックに `npx playwright test` を追加

**`.claude/settings.json`**

- `gh pr create` フックに `npx playwright test` を追加（Pint → PHPUnit → Playwright の順）

## 既存ファイルとの関係

- `tests/e2e/specs/auth.spec.ts`: 既存。`helpers/db.ts` を参照しているため、今回作成で動作するようになる
- `tests/e2e/pages/LoginPage.ts`: 既存。そのまま流用
- `LocalTestDataSeeder`: 既存。`test@example.com/password`・7カテゴリ・65件取引を作成

## 前提条件

- `npx playwright install chromium` でブラウザをインストール済みであること
- テスト実行前に Docker コンテナが起動済みであること（`docker compose up -d`）
