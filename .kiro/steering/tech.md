# Technology Stack

## Architecture

- **Backend**: Laravel 12（MVC + Service 層）
- **Frontend**: Blade + Vite + Tailwind CSS + Alpine.js（SPA ではなくサーバーサイドレンダリング主体）
- **Auth**: Laravel Breeze（認証・メール確認・パスワードリセット）
- **Authorization**: Policy によるリソース単位のアクセス制御（各 Model に対応 Policy）

## Core Technologies

- **Language**: PHP 8.2+
- **Framework**: Laravel 12
- **Database**: MySQL 8.0+（開発は Docker Compose で MySQL）
- **Frontend**: Node.js 20+（Vite 7, Tailwind 3, Alpine.js 3）

## Key Libraries

- **Excel/CSV**: Maatwebsite/Excel（インポート・エクスポート）
- **日付**: Carbon（Laravel 標準）
- **スタイル**: Tailwind CSS, @tailwindcss/forms
- **テスト**: PHPUnit 11, Laravel Pint（コードスタイル）

## Development Standards

### Type Safety

- PHP: 型ヒント・戻り値型を積極的に使用（Eloquent の relations など）
- フロントは Blade + Alpine.js のため TypeScript は未採用

### Code Quality

- Laravel Pint で PHP コードスタイルを統一
- CI で `composer test`（= `php artisan test`）および Pint を実行

### Testing

- PHPUnit: Unit / Feature テストスイート
- テスト時 DB: SQLite in-memory（phpunit.xml で設定）
- 認証・プロフィール・パスワード関連の Feature テストあり

## Development Environment

### Required Tools

- PHP 8.2+, Composer, Node.js 20+, npm
- MySQL 8.0+（または Docker Compose 利用）
- 本番デプロイ: SSH 鍵、GitHub Actions 用 Secrets（SSH_*, DEPLOY_PATH）

### Common Commands

```bash
# 開発サーバー・キュー・ログ・Vite の一括起動
composer dev

# テスト
composer test

# ビルド
npm run build

# 定期支出の自動展開（月初実行想定）
php artisan recurring:generate
```

## Key Technical Decisions

- **マルチテナント**: `user_id` でスコープし、Policy で認可。全リソースがユーザー単位で分離。
- **デプロイ**: GitHub Actions で main プッシュ時にテスト → ビルド → リリースディレクトリ方式でデプロイ。`current` シンボリックリンクで切り替え、ロールバック可能。
- **トップページ**: セキュリティのため `/` は 404。入口はログイン/登録と `/dashboard`。

---
_Document standards and patterns, not every dependency_
