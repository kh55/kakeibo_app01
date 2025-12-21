# 家計簿Webアプリ

Laravel + MySQLで構築された家計簿管理Webアプリケーションです。

## 機能

- 取引明細の管理（収入/支出）
- 予算管理
- 定期支出の自動展開
- 分割払い管理
- キャッシュフロー予定表
- CSVインポート/エクスポート

## 環境要件

- PHP 8.2以上
- MySQL 8.0以上
- Composer
- Node.js / npm（フロントエンドビルド用）

## セットアップ（Docker環境）

### 1. 環境変数の設定

`.env.example`をコピーして`.env`を作成し、必要に応じて設定を変更してください。

```bash
cp .env.example .env
```

### 2. Dockerコンテナの起動

```bash
docker compose up -d
```

### 3. 依存パッケージのインストール

```bash
docker compose run --rm app composer install
docker compose run --rm app npm install
```

### 4. アプリケーションキーの生成

```bash
docker compose run --rm app php artisan key:generate
```

### 5. データベースマイグレーション

```bash
docker compose run --rm app php artisan migrate
```

### 6. アセットのビルド

```bash
docker compose run --rm app npm run build
```

### 7. アクセス

ブラウザで `http://localhost:8080` にアクセスしてください。

## 開発

### コマンド実行

```bash
docker compose run --rm app php artisan [command]
docker compose run --rm app npm [command]
```

### 定期支出の自動生成

月初に定期支出を自動生成するコマンド：

```bash
docker compose run --rm app php artisan recurring:generate
```

## デプロイ

GitHub Actionsによる自動デプロイが設定されています。

### 必要なSecrets

- `SSH_PRIVATE_KEY`: サーバーへのSSH秘密鍵
- `SSH_HOST`: サーバーのホスト名
- `SSH_USER`: SSHユーザー名
- `DEPLOY_PATH`: デプロイ先のパス（例: /home/user/public_html）
- `SSH_PASSPHRASE`: SSH秘密鍵のパスフレーズ（パスフレーズがある場合のみ。パスフレーズなしの鍵を使用する場合は設定不要）

## セキュリティ

- HTTPS強制（本番環境）
- CSRF保護（Laravel標準）
- XSS対策（Bladeのエスケープ）
- アクセス制限（Basic認証またはIP制限を推奨）

## ライセンス

MIT
