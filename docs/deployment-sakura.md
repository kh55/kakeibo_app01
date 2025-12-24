# さくらインターネットレンタルサーバー向けデプロイガイド

このガイドでは、さくらインターネットのレンタルサーバーに本アプリケーションをデプロイする手順を説明します。

## さくらインターネットのレンタルサーバー仕様

- **Webサーバー**: Apache
- **PHP**: 8.2以上（サーバーパネルで設定可能）
- **データベース**: MySQL 8.0（`mysql80.ユーザー名.sakura.ne.jp`）
- **SSH接続**: 対応（SSHサーバー: `sshXXX.sakura.ne.jp`）
- **利用可能なツール**: Git、Composer、Node.js、npm等
- **ドキュメントルート**: 
  - メインドメイン: `/home/ユーザー名/www/ドメイン名/`
  - サブドメイン: `/home/ユーザー名/www/サブドメイン.ドメイン名/`（例: `/home/username/www/app.yourdomain.com/`）

## デプロイ先のパス構造

```
/home/username/www/yourdomain.com/app/
├── releases/          # リリース履歴
├── shared/            # 共有リソース
│   └── storage/      # ストレージ
├── current -> releases/YYYYMMDD_HHMMSS/  # 現在のリリースへのシンボリックリンク
└── .env              # 環境変数ファイル
```

## 設定手順

### 1. ディレクトリ構造の作成

```bash
# サーバーにSSH接続
ssh username@yourdomain.com

# デプロイディレクトリの作成
mkdir -p /home/username/www/yourdomain.com/app/{releases,shared/storage}
chmod -R 755 /home/username/www/yourdomain.com/app
chmod -R 775 /home/username/www/yourdomain.com/app/shared/storage
```

### 2. ルートディレクトリに.htaccessファイルを作成

さくらインターネットでは、ドキュメントルートは通常 `/home/username/www/yourdomain.com/` になります。
`app/` ディレクトリにアクセスした際に `current/public` にリダイレクトするため、以下の`.htaccess`ファイルを `/home/username/www/yourdomain.com/app/.htaccess` に配置します：

```bash
# プロジェクトルートの .htaccess.sakura をコピー
cp .htaccess.sakura /home/username/www/yourdomain.com/app/.htaccess
```

または、手動で以下の内容を `/home/username/www/yourdomain.com/app/.htaccess` に作成：

```apache
<IfModule mod_rewrite.c>
    RewriteEngine On
    
    # ファイルやディレクトリが存在しない場合のみリダイレクト
    RewriteCond %{REQUEST_FILENAME} !-f
    RewriteCond %{REQUEST_FILENAME} !-d
    
    # current/public ディレクトリが存在する場合のみリダイレクト
    RewriteCond %{DOCUMENT_ROOT}/app/current/public -d
    
    # current/public にリダイレクト
    RewriteRule ^(.*)$ /app/current/public/$1 [L]
</IfModule>
```

**注意**: プロジェクトルートに `.htaccess.sakura` というテンプレートファイルが用意されています。これをデプロイ先の `app/` ディレクトリに `.htaccess` として配置してください。

### 3. サブドメインを使用する場合（app.yourdomain.com）

**推奨方法**: さくらのサーバーパネルで、サブドメイン `app.yourdomain.com` のドキュメントルートを直接 `/home/username/www/yourdomain.com/app/current/public` に設定してください。
この場合、`.htaccess`ファイルは不要です（`public/.htaccess`のみ使用）。

**代替方法**: サブドメインのドキュメントルートが `/home/username/www/app.yourdomain.com/` の場合、そこに`.htaccess`ファイルを配置してリダイレクトします：
```bash
# サブドメインのドキュメントルートに.htaccessを配置
cp .htaccess.sakura /home/username/www/app.yourdomain.com/.htaccess
```

### 4. さくらのサーバーパネルでの設定（メインドメインの場合）

さくらのサーバーパネルで、ドメイン `yourdomain.com` のドキュメントルートを `/home/username/www/yourdomain.com/app/current/public` に変更できる場合は、そちらを推奨します。
この場合、上記の`.htaccess`ファイルは不要です。

### 5. パーミッションの設定

```bash
# storageディレクトリのパーミッション設定
chmod -R 775 /home/username/www/yourdomain.com/app/shared/storage
chmod -R 775 /home/username/www/yourdomain.com/app/current/storage
chmod -R 775 /home/username/www/yourdomain.com/app/current/bootstrap/cache
```

### 6. PHPバージョンの確認

さくらインターネットのレンタルサーバーでは、PHP 8.2以上が利用可能です。
サーバーパネルでPHPバージョンを確認・設定してください。

### 7. .envファイルの設定

```bash
# .envファイルを配置
nano /home/username/www/yourdomain.com/app/.env
```

`.env`ファイルの設定例（さくらインターネット向け）:
```env
APP_NAME=Laravel
APP_ENV=production
APP_KEY=base64:...（php artisan key:generateで生成）
APP_DEBUG=false
APP_URL=https://app.yourdomain.com

DB_CONNECTION=mysql
DB_HOST=mysql80.username.sakura.ne.jp
DB_PORT=3306
DB_DATABASE=username_app01
DB_USERNAME=username_app01
DB_PASSWORD=your_password

# その他の設定...
```

**注意事項**:
- さくらインターネットのレンタルサーバーでは、`/home/ユーザー名/www/ドメイン名/` がドキュメントルートになります
- サブディレクトリにアプリを配置する場合、パスの調整が必要です
- サブドメインを使用する場合、ドキュメントルートを直接設定することを推奨します
- `APP_URL`には、実際のアクセスURLを設定してください
  - サブドメインの場合: `https://app.yourdomain.com`
  - サブディレクトリの場合: `https://yourdomain.com/app`

## GitHub Secretsの設定

GitHubリポジトリの **Settings → Secrets and variables → Actions** で以下を設定してください。

**さくらインターネット向けの設定例**:
- `SSH_HOST`: `yourdomain.com` または `sshXXX.sakura.ne.jp`（さくらのSSHサーバー）
- `SSH_USER`: `username`（さくらのアカウント名）
- `DEPLOY_PATH`: `/home/username/www/yourdomain.com/app`

詳細は[README.md](../README.md)の「GitHub Secretsの設定」セクションを参照してください。

## トラブルシューティング

### 404 Not Found または ページが表示されない

**解決方法**:
1. `.htaccess`ファイルが正しく配置されているか確認
   ```bash
   ls -la /home/username/www/yourdomain.com/app/.htaccess
   ```
2. `current`シンボリックリンクが正しく設定されているか確認
   ```bash
   ls -la /home/username/www/yourdomain.com/app/current
   ```
3. `current/public`ディレクトリが存在するか確認
   ```bash
   ls -la /home/username/www/yourdomain.com/app/current/public
   ```
4. さくらのサーバーパネルで、PHPバージョンが8.2以上に設定されているか確認

### データベース接続エラー

**解決方法**:
1. `.env`ファイルのデータベース設定を確認
   - `DB_HOST`: `mysql80.username.sakura.ne.jp`（さくらのMySQLサーバー）
   - `DB_DATABASE`: さくらのデータベース名
   - `DB_USERNAME`: さくらのデータベースユーザー名
   - `DB_PASSWORD`: さくらのデータベースパスワード
2. さくらのサーバーパネルで、データベースが作成されているか確認
3. データベースユーザーに適切な権限が付与されているか確認

### パーミッションエラー（ログやキャッシュが書き込めない）

**解決方法**:
```bash
# storageディレクトリのパーミッションを設定
chmod -R 775 /home/username/www/yourdomain.com/app/shared/storage
chmod -R 775 /home/username/www/yourdomain.com/app/current/storage
chmod -R 775 /home/username/www/yourdomain.com/app/current/bootstrap/cache

# 所有者を確認（必要に応じて変更）
chown -R username:username /home/username/www/yourdomain.com/app/shared/storage
```

### APP_KEYが設定されていない

**解決方法**:
```bash
# サーバーにSSH接続して、アプリケーションキーを生成
cd /home/username/www/yourdomain.com/app/current
php artisan key:generate
# 生成されたキーを .env ファイルにコピー
```

