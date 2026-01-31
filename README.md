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

### テスト

テストは Docker 環境で実行します。詳細は [docs/test.md](docs/test.md) を参照してください。

```bash
docker compose run --rm app php artisan test
```

### プッシュ前チェック（CI と同じ検証）

main へのプッシュで動く GitHub Actions では、PHPUnit と **Laravel Pint**（コードスタイル）が実行されます。デプロイ失敗を防ぐため、プッシュ前に同じチェックをローカルで実行することを推奨します。

```bash
# スタイルチェックのみ（CI と同じ。問題があれば失敗）
docker compose run --rm app composer pint:test

# スタイルを自動修正してからコミット
docker compose run --rm app composer pint
```

PHP を直接使う場合: `composer pint:test` / `composer pint`

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

##### ホスティングプロバイダー別の設定

- **さくらインターネットのレンタルサーバー**: [さくらインターネット向けデプロイガイド](docs/deployment-sakura.md)を参照してください。

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

**ホスティングプロバイダー別の設定例**:
- **さくらインターネット**: [さくらインターネット向けデプロイガイド](docs/deployment-sakura.md)を参照してください。

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

#### ホスティングプロバイダー特有の問題

**さくらインターネット**: [さくらインターネット向けデプロイガイド](docs/deployment-sakura.md)の「トラブルシューティング」セクションを参照してください。

## セキュリティ

- HTTPS強制（本番環境）
- CSRF保護（Laravel標準）
- XSS対策（Bladeのエスケープ）
- アクセス制限（Basic認証またはIP制限を推奨）

## ライセンス

MIT
