# テスト

本プロジェクトのテストは **Docker 環境** で実行することを想定しています。

## 前提

- `docker compose up -d` でアプリ用コンテナ（`app`）が起動していること
- `docker compose run --rm app composer install` 済みであること

テスト時は `phpunit.xml` の設定により **SQLite インメモリ** が使われます（MySQL は不要）。

## 実行方法

### 全テスト

```bash
docker compose run --rm app php artisan test
```

または Composer スクリプト経由:

```bash
docker compose run --rm app composer test
```

### 特定のテストファイル

```bash
docker compose run --rm app php artisan test tests/Feature/BudgetControllerTest.php
```

### 特定のテストメソッド

```bash
docker compose run --rm app php artisan test tests/Feature/BudgetControllerTest.php --filter=test_index_view_shows_actual_remaining
```

## CI（GitHub Actions）

デプロイワークフロー内では、CI 環境で `composer test` が実行されます。ローカルで Docker を使う場合も上記コマンドで同じようにテストできます。
