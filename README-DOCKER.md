# Docker環境セットアップガイド

このプロジェクトのDocker環境セットアップ手順です。

## 必要な環境

- Docker Desktop (Mac/Windows)
- Docker Compose

## クイックスタート

```bash
# セットアップスクリプトを実行
./docker-setup.sh
```

アプリケーションは `http://localhost:8080` でアクセスできます。

## 手動セットアップ

セットアップスクリプトを使わない場合は、以下のコマンドを順番に実行してください。

### 1. 環境変数の設定

```bash
cp .env.docker .env
```

### 2. Dockerコンテナのビルドと起動

```bash
docker-compose up -d --build
```

### 3. 依存関係のインストール

```bash
# Composer
docker-compose exec app composer install

# NPM
docker-compose exec app npm install
```

### 4. Laravelの初期設定

```bash
# アプリケーションキーの生成
docker-compose exec app php artisan key:generate

# ストレージリンクの作成
docker-compose exec app php artisan storage:link

# マイグレーションの実行
docker-compose exec app php artisan migrate
```

### 5. アセットのコンパイル

```bash
docker-compose exec app npm run dev
```

## Docker構成

このプロジェクトは以下のサービスで構成されています：

- **app**: PHP 8.2 + Nginx + Node.js (ポート: 8080)
- **db**: MySQL 8.0 (ポート: 3306)
- **redis**: Redis Alpine (ポート: 6379)

## よく使うコマンド

### コンテナの起動・停止

```bash
# コンテナを起動
docker-compose up -d

# コンテナを停止
docker-compose down

# コンテナを再起動
docker-compose restart
```

### ログの確認

```bash
# すべてのログを表示
docker-compose logs -f

# appコンテナのログのみ表示
docker-compose logs -f app
```

### コンテナ内でコマンドを実行

```bash
# コンテナ内に入る
docker-compose exec app bash

# Artisanコマンドの実行
docker-compose exec app php artisan migrate
docker-compose exec app php artisan cache:clear
docker-compose exec app php artisan config:clear

# Composerコマンドの実行
docker-compose exec app composer update

# NPMコマンドの実行
docker-compose exec app npm run watch
```

### データベース接続

```bash
# MySQLコンテナに接続
docker-compose exec db mysql -u y2user -py2password y2_official
```

### データベースのリセット

```bash
docker-compose exec app php artisan migrate:fresh --seed
```

## トラブルシューティング

### ポートが既に使用されている

ポート8080が既に使用されている場合は、[docker-compose.yml](docker-compose.yml) の `ports` セクションを変更してください。

```yaml
ports:
  - "8000:80"  # 8080を8000に変更
```

### 権限エラー

```bash
docker-compose exec app chown -R www-data:www-data /var/www/html/storage
docker-compose exec app chmod -R 755 /var/www/html/storage
```

### コンテナの完全リセット

```bash
# コンテナ、ボリューム、ネットワークをすべて削除
docker-compose down -v

# 再度ビルドと起動
docker-compose up -d --build
```

## 環境変数

Docker環境用の環境変数は [.env.docker](.env.docker) に定義されています。

主な設定：
- `DB_HOST=db` (MySQLコンテナ名)
- `DB_DATABASE=y2_official`
- `DB_USERNAME=y2user`
- `DB_PASSWORD=y2password`
- `REDIS_HOST=redis` (Redisコンテナ名)

## 本番環境への移行

本番環境では以下の変更を推奨します：

1. `APP_ENV=production` に変更
2. `APP_DEBUG=false` に設定
3. 強固なデータベースパスワードを設定
4. 適切なドメインを `APP_URL` に設定
5. HTTPS証明書の設定
