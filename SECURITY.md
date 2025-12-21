# セキュリティ設定ガイド

## HTTPS強制

本番環境では、`.env`で以下の設定を行ってください：

```env
APP_ENV=production
APP_DEBUG=false
```

また、Webサーバー（Nginx/Apache）でHTTPSを強制してください。

### Nginx設定例

```nginx
server {
    listen 80;
    server_name example.com;
    return 301 https://$server_name$request_uri;
}

server {
    listen 443 ssl;
    server_name example.com;
    
    ssl_certificate /path/to/cert.pem;
    ssl_certificate_key /path/to/key.pem;
    
    # ... その他の設定
}
```

## アクセス制限

### Basic認証の設定

`.htaccess`ファイル（Apacheの場合）またはNginx設定でBasic認証を設定できます。

### IP制限

Nginx設定例：

```nginx
location / {
    allow 192.168.1.0/24;
    allow 10.0.0.0/8;
    deny all;
    # ... その他の設定
}
```

## CSRF保護

Laravelの標準機能により、すべてのPOST/PUT/DELETEリクエストでCSRFトークンが検証されます。

## XSS対策

Bladeテンプレートでは、自動的にエスケープが行われます。生のHTMLを出力する場合は`{!! !!}`を使用する際は注意してください。

## データベースバックアップ

日次バックアップを推奨します。以下のコマンドでバックアップを取得できます：

```bash
mysqldump -u username -p database_name > backup_$(date +%Y%m%d).sql
```

バックアップファイルは暗号化して安全な場所に保管してください。

