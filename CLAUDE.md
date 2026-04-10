# CLAUDE.md

## 作業開始時のルール

作業を開始する最初に、main ブランチを最新にしてからブランチを切って開始する。

```bash
git checkout main
git pull
git checkout -b <branch-name>
```

## 技術スタック

- **バックエンド**: Laravel 12 / PHP 8.2
- **フロントエンド**: Bootstrap 5 + Alpine.js（どちらも CDN）
- **テスト**: PHPUnit / SQLite in-memory（`php artisan test`）
- **コードスタイル**: Laravel Pint（`vendor/bin/pint`）
- **ビルド**: Vite（`npm run build`）

## コーディング規約

### PHP

- テストメソッド名はスネークケース（例: `test_get_monthly_summary_returns_savings_rate`）
- クラスプロパティ間には空行を入れる
- 引数なしの `new` は括弧を省略（`new Foo` ✅ / `new Foo()` ❌）
- コミットメッセージは Conventional Commits 形式（`feat:` `fix:` `test:` `docs:` `chore:`）

### CI チェック項目

PR マージ前に以下が通ること：

1. `vendor/bin/phpunit` — テスト全件 PASS
2. `vendor/bin/pint --test` — スタイルチェック PASS

スタイルエラーは `vendor/bin/pint` で自動修正できる。

## PHP の実行環境

PHP はローカルに直接インストールされていない。コマンドはすべて Docker 経由で実行する。

```bash
# テスト実行
docker compose exec -T app vendor/bin/phpunit

# スタイルチェック
docker compose exec -T app vendor/bin/pint --test

# スタイル自動修正
docker compose exec -T app vendor/bin/pint

# Artisan コマンド
docker compose exec app php artisan <command>
```

## データベース

- 開発・テストは SQLite in-memory（`phpunit.xml` で設定済み）
- マイグレーションは冪等性を保つこと（MySQL の DDL 制約に注意）
