#!/bin/bash

echo "🚀 Y2 Official Docker Setup Starting..."

# .envファイルをコピー
if [ ! -f .env ]; then
    echo "📝 Creating .env file from .env.docker..."
    cp .env.docker .env
else
    echo "⚠️  .env file already exists, skipping..."
fi

# Dockerコンテナをビルドして起動
echo "🐳 Building and starting Docker containers..."
docker-compose up -d --build

# コンテナが起動するまで待機
echo "⏳ Waiting for containers to start..."
sleep 10

# Composerの依存関係をインストール
echo "📦 Installing Composer dependencies..."
docker-compose exec app composer install

# NPMの依存関係をインストール
echo "📦 Installing NPM dependencies..."
docker-compose exec app npm install

# アプリケーションキーを生成
echo "🔑 Generating application key..."
docker-compose exec app php artisan key:generate

# ストレージリンクを作成
echo "🔗 Creating storage link..."
docker-compose exec app php artisan storage:link

# マイグレーションを実行
echo "🗄️  Running database migrations..."
docker-compose exec app php artisan migrate

# アセットをコンパイル
echo "🎨 Compiling assets..."
docker-compose exec app npm run dev

echo "✅ Setup complete!"
echo "🌐 Application is running at: http://localhost:8080"
echo ""
echo "Useful commands:"
echo "  docker-compose up -d          # Start containers"
echo "  docker-compose down           # Stop containers"
echo "  docker-compose logs -f app    # View logs"
echo "  docker-compose exec app bash  # Enter container"
