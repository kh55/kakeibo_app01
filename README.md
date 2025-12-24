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

GitHub Actionsによる自動デプロイが設定されています。ゼロダウンタイムデプロイ方式を採用しており、リリース管理と自動ロールバック機能を備えています。

### デプロイの流れ

1. **テスト実行**: コードスタイルチェック、ユニットテスト、機能テストを実行
2. **本番ビルド**: 本番環境用に最適化されたビルドを作成
3. **デプロイ**: サーバーへのアップロードと設定

### サーバー側の準備

#### 1. ディレクトリ構造の作成

デプロイ先のディレクトリ構造を準備します：

```bash
# デプロイパス（DEPLOY_PATHで指定するパス）
/path/to/deploy/
├── releases/          # リリース履歴（最新5件を保持）
│   ├── 20231221_120000/
│   └── 20231221_110000/
├── shared/            # 共有リソース
│   └── storage/      # ストレージ（ログ、キャッシュなど）
├── current -> releases/20231221_120000/  # 現在のリリースへのシンボリックリンク
└── .env              # 環境変数ファイル
```

#### 2. ディレクトリとパーミッションの設定

```bash
# デプロイパスの作成
mkdir -p /path/to/deploy/{releases,shared/storage}
chmod -R 755 /path/to/deploy
chmod -R 775 /path/to/deploy/shared/storage

# .envファイルの配置
cp .env.example /path/to/deploy/.env
# .envファイルを編集して本番環境の設定を行う
nano /path/to/deploy/.env
```

#### 3. SSH鍵の設定

```bash
# サーバー側で公開鍵をauthorized_keysに追加
mkdir -p ~/.ssh
chmod 700 ~/.ssh
cat >> ~/.ssh/authorized_keys << EOF
[GitHub SecretsのSSH_PRIVATE_KEYに対応する公開鍵を貼り付け]
EOF
chmod 600 ~/.ssh/authorized_keys
```

**公開鍵の取得方法**:
```bash
# ローカルマシンで秘密鍵から公開鍵を抽出
ssh-keygen -y -f ~/.ssh/your_private_key
```

#### 4. Webサーバーの設定

##### さくらインターネットのレンタルサーバー向け設定

さくらインターネットのレンタルサーバーでは、Apacheが使用されており、`.htaccess`ファイルで設定を行います。

**さくらインターネットのレンタルサーバー仕様**:
- **Webサーバー**: Apache
- **PHP**: 8.2以上（サーバーパネルで設定可能）
- **データベース**: MySQL 8.0（`mysql80.ユーザー名.sakura.ne.jp`）
- **SSH接続**: 対応（SSHサーバー: `sshXXX.sakura.ne.jp`）
- **利用可能なツール**: Git、Composer、Node.js、npm等
- **ドキュメントルート**: 
  - メインドメイン: `/home/ユーザー名/www/ドメイン名/`
  - サブドメイン: `/home/ユーザー名/www/サブドメイン.ドメイン名/`（例: `/home/crossroad2u/www/kakeibo.crossroad-j.info/`）

**デプロイ先のパス構造**:
```
/home/crossroad2u/www/crossroad-j.info/kakeibo/
├── releases/          # リリース履歴
├── shared/            # 共有リソース
│   └── storage/      # ストレージ
├── current -> releases/YYYYMMDD_HHMMSS/  # 現在のリリースへのシンボリックリンク
└── .env              # 環境変数ファイル
```

**設定手順**:

1. **ディレクトリ構造の作成**:
```bash
# サーバーにSSH接続
ssh crossroad2u@crossroad-j.info

# デプロイディレクトリの作成
mkdir -p /home/crossroad2u/www/crossroad-j.info/kakeibo/{releases,shared/storage}
chmod -R 755 /home/crossroad2u/www/crossroad-j.info/kakeibo
chmod -R 775 /home/crossroad2u/www/crossroad-j.info/kakeibo/shared/storage
```

2. **ルートディレクトリに.htaccessファイルを作成**:
さくらインターネットでは、ドキュメントルートは通常 `/home/crossroad2u/www/crossroad-j.info/` になります。
`kakeibo/` ディレクトリにアクセスした際に `current/public` にリダイレクトするため、以下の`.htaccess`ファイルを `/home/crossroad2u/www/crossroad-j.info/kakeibo/.htaccess` に配置します：

```bash
# プロジェクトルートの .htaccess.sakura をコピー
cp .htaccess.sakura /home/crossroad2u/www/crossroad-j.info/kakeibo/.htaccess
```

または、手動で以下の内容を `/home/crossroad2u/www/crossroad-j.info/kakeibo/.htaccess` に作成：

```apache
<IfModule mod_rewrite.c>
    RewriteEngine On
    
    # ファイルやディレクトリが存在しない場合のみリダイレクト
    RewriteCond %{REQUEST_FILENAME} !-f
    RewriteCond %{REQUEST_FILENAME} !-d
    
    # current/public ディレクトリが存在する場合のみリダイレクト
    RewriteCond %{DOCUMENT_ROOT}/kakeibo/current/public -d
    
    # current/public にリダイレクト
    RewriteRule ^(.*)$ /kakeibo/current/public/$1 [L]
</IfModule>
```

**注意**: プロジェクトルートに `.htaccess.sakura` というテンプレートファイルが用意されています。これをデプロイ先の `kakeibo/` ディレクトリに `.htaccess` として配置してください。

3. **サブドメインを使用する場合（kakeibo.crossroad-j.info）**:

**推奨方法**: さくらのサーバーパネルで、サブドメイン `kakeibo.crossroad-j.info` のドキュメントルートを直接 `/home/crossroad2u/www/crossroad-j.info/kakeibo/current/public` に設定してください。
この場合、`.htaccess`ファイルは不要です（`public/.htaccess`のみ使用）。

**代替方法**: サブドメインのドキュメントルートが `/home/crossroad2u/www/kakeibo.crossroad-j.info/` の場合、そこに`.htaccess`ファイルを配置してリダイレクトします：
```bash
# サブドメインのドキュメントルートに.htaccessを配置
cp .htaccess.sakura /home/crossroad2u/www/kakeibo.crossroad-j.info/.htaccess
```

4. **さくらのサーバーパネルでの設定（メインドメインの場合）**:
さくらのサーバーパネルで、ドメイン `crossroad-j.info` のドキュメントルートを `/home/crossroad2u/www/crossroad-j.info/kakeibo/current/public` に変更できる場合は、そちらを推奨します。
この場合、上記の`.htaccess`ファイルは不要です。

5. **パーミッションの設定**:
```bash
# storageディレクトリのパーミッション設定
chmod -R 775 /home/crossroad2u/www/crossroad-j.info/kakeibo/shared/storage
chmod -R 775 /home/crossroad2u/www/crossroad-j.info/kakeibo/current/storage
chmod -R 775 /home/crossroad2u/www/crossroad-j.info/kakeibo/current/bootstrap/cache
```

6. **PHPバージョンの確認**:
さくらインターネットのレンタルサーバーでは、PHP 8.2以上が利用可能です。
サーバーパネルでPHPバージョンを確認・設定してください。

7. **.envファイルの設定**:
```bash
# .envファイルを配置
nano /home/crossroad2u/www/crossroad-j.info/kakeibo/.env
```

`.env`ファイルの設定例（さくらインターネット向け）:
```env
APP_NAME=Laravel
APP_ENV=production
APP_KEY=base64:...（php artisan key:generateで生成）
APP_DEBUG=false
APP_URL=https://kakeibo.crossroad-j.info

DB_CONNECTION=mysql
DB_HOST=mysql80.crossroad2u.sakura.ne.jp
DB_PORT=3306
DB_DATABASE=crossroad2u_kakeibo01
DB_USERNAME=crossroad2u_kakeibo01
DB_PASSWORD=your_password

# その他の設定...
```

**注意事項**:
- さくらインターネットのレンタルサーバーでは、`/home/ユーザー名/www/ドメイン名/` がドキュメントルートになります
- サブディレクトリにアプリを配置する場合、パスの調整が必要です
- サブドメインを使用する場合、ドキュメントルートを直接設定することを推奨します
- `APP_URL`には、実際のアクセスURLを設定してください
  - サブドメインの場合: `https://kakeibo.crossroad-j.info`
  - サブディレクトリの場合: `https://crossroad-j.info/kakeibo`

##### その他のWebサーバー（Nginx等）

NginxやApacheで直接設定できる場合の例：

**Nginx例**:
```nginx
server {
    root /path/to/deploy/current/public;
    index index.php;
    
    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }
    
    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.2-fpm.sock;
        fastcgi_index index.php;
        include fastcgi_params;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
    }
}
```

### GitHub Secretsの設定

GitHubリポジトリの **Settings → Secrets and variables → Actions** で以下を設定してください。

**Repository secrets**（推奨）または**Environment secrets**のどちらでも使用できます。

#### 必須のSecrets

- `SSH_PRIVATE_KEY`: サーバーへのSSH秘密鍵（完全な形式、`-----BEGIN ... PRIVATE KEY-----`から`-----END ... PRIVATE KEY-----`まで含む）
- `SSH_HOST`: サーバーのホスト名またはIPアドレス（例: `example.com` または `192.168.1.100`）
- `SSH_USER`: SSHユーザー名（例: `deploy` または `www-data`）
- `DEPLOY_PATH`: デプロイ先のパス（例: `/var/www/app` または `/home/user/public_html`）

**さくらインターネット向けの設定例**:
- `SSH_HOST`: `crossroad-j.info` または `sshXXX.sakura.ne.jp`（さくらのSSHサーバー）
- `SSH_USER`: `crossroad2u`（さくらのアカウント名）
- `DEPLOY_PATH`: `/home/crossroad2u/www/crossroad-j.info/kakeibo`

#### オプションのSecrets

- `SSH_PASSPHRASE`: SSH秘密鍵のパスフレーズ（パスフレーズ付き鍵を使用する場合のみ設定。パスフレーズなしの鍵を使用する場合は設定不要）

**注意**: 複数の環境（staging, production）を分けたい場合は、Environment secretsを使用し、ワークフローファイルで`environment`を指定してください。

### デプロイの実行

`main`ブランチにプッシュすると、自動的にデプロイが開始されます：

```bash
git push origin main
```

デプロイの進行状況は、GitHubリポジトリの **Actions** タブで確認できます。

### デプロイ時の自動処理

デプロイ中に以下の処理が自動的に実行されます：

1. **バックアップ作成**: 現在のデプロイをタイムスタンプ付きでバックアップ
2. **ファイルアップロード**: ビルド済みのアプリケーションファイルをアップロード
3. **共有リソースのリンク**: `storage`ディレクトリを共有リソースにリンク
4. **環境変数のコピー**: `.env`ファイルを新しいリリースにコピー
5. **データベースマイグレーション**: `php artisan migrate --force`を実行
6. **キャッシュクリア**: 設定、ルート、ビューのキャッシュをクリア
7. **最適化**: 設定、ルート、ビューのキャッシュを再生成
8. **リリース切り替え**: `current`シンボリックリンクを新しいリリースに更新
9. **古いリリースの削除**: 最新5件以外のリリースを自動削除

### ロールバック

問題が発生した場合、手動でロールバックできます：

```bash
# サーバーにSSH接続
ssh user@server

# 前のリリースに切り替え
cd /path/to/deploy
ls -la releases/  # 利用可能なリリースを確認
rm current
ln -s releases/20231221_110000 current  # 前のリリースに切り替え
```

### トラブルシューティング

#### SSH接続エラー

**エラー**: `Permission denied (publickey,password)`

**解決方法**:
1. GitHub Secretsの`SSH_PRIVATE_KEY`が正しく設定されているか確認
2. サーバー側の`~/.ssh/authorized_keys`に公開鍵が登録されているか確認
3. SSH鍵のフォーマットが正しいか確認（BEGIN/END行を含む）
4. ワークフローのログで公開鍵の内容を確認し、サーバーに登録されているか確認

#### デプロイ後のエラー

**エラー**: アプリケーションが正常に動作しない

**確認項目**:
1. `.env`ファイルが正しく設定されているか
2. データベース接続設定が正しいか
3. ストレージディレクトリのパーミッション（`775`）が設定されているか
4. Webサーバーのドキュメントルートが`current/public`を指しているか

#### マイグレーションエラー

**エラー**: マイグレーションが失敗する

**解決方法**:
- サーバーにSSH接続して手動でマイグレーションを実行
- データベースのバックアップを確認
- マイグレーションファイルに問題がないか確認

#### さくらインターネット特有の問題

**エラー**: 404 Not Found または ページが表示されない

**解決方法**:
1. `.htaccess`ファイルが正しく配置されているか確認
   ```bash
   ls -la /home/crossroad2u/www/crossroad-j.info/kakeibo/.htaccess
   ```
2. `current`シンボリックリンクが正しく設定されているか確認
   ```bash
   ls -la /home/crossroad2u/www/crossroad-j.info/kakeibo/current
   ```
3. `current/public`ディレクトリが存在するか確認
   ```bash
   ls -la /home/crossroad2u/www/crossroad-j.info/kakeibo/current/public
   ```
4. さくらのサーバーパネルで、PHPバージョンが8.2以上に設定されているか確認

**エラー**: データベース接続エラー

**解決方法**:
1. `.env`ファイルのデータベース設定を確認
   - `DB_HOST`: `mysql80.crossroad2u.sakura.ne.jp`（さくらのMySQLサーバー）
   - `DB_DATABASE`: さくらのデータベース名
   - `DB_USERNAME`: さくらのデータベースユーザー名
   - `DB_PASSWORD`: さくらのデータベースパスワード
2. さくらのサーバーパネルで、データベースが作成されているか確認
3. データベースユーザーに適切な権限が付与されているか確認

**エラー**: パーミッションエラー（ログやキャッシュが書き込めない）

**解決方法**:
```bash
# storageディレクトリのパーミッションを設定
chmod -R 775 /home/crossroad2u/www/crossroad-j.info/kakeibo/shared/storage
chmod -R 775 /home/crossroad2u/www/crossroad-j.info/kakeibo/current/storage
chmod -R 775 /home/crossroad2u/www/crossroad-j.info/kakeibo/current/bootstrap/cache

# 所有者を確認（必要に応じて変更）
chown -R crossroad2u:crossroad2u /home/crossroad2u/www/crossroad-j.info/kakeibo/shared/storage
```

**エラー**: APP_KEYが設定されていない

**解決方法**:
```bash
# サーバーにSSH接続して、アプリケーションキーを生成
cd /home/crossroad2u/www/crossroad-j.info/kakeibo/current
php artisan key:generate
# 生成されたキーを .env ファイルにコピー
```

## セキュリティ

- HTTPS強制（本番環境）
- CSRF保護（Laravel標準）
- XSS対策（Bladeのエスケープ）
- アクセス制限（Basic認証またはIP制限を推奨）

## ライセンス

MIT
