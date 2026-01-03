FROM php:8.2-fpm

# 作業ディレクトリを設定
WORKDIR /var/www/html

# 必要なシステムパッケージをインストール
RUN apt-get update && apt-get install -y \
    git \
    curl \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    zip \
    unzip \
    libzip-dev \
    nginx \
    supervisor \
    && docker-php-ext-install pdo_mysql mbstring exif pcntl bcmath gd zip \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

# Composerをインストール
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Node.jsとnpmをインストール (Laravel Mixのため)
RUN curl -fsSL https://deb.nodesource.com/setup_18.x | bash - \
    && apt-get install -y nodejs \
    && npm install -g npm@latest

# Nginxの設定をコピー
COPY docker/nginx/default.conf /etc/nginx/sites-available/default

# Supervisorの設定をコピー
COPY docker/supervisor/supervisord.conf /etc/supervisor/conf.d/supervisord.conf

# アプリケーションファイルをコピー
COPY . /var/www/html

# 権限を設定
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html/storage \
    && chmod -R 755 /var/www/html/bootstrap/cache

# ポートを公開
EXPOSE 80

# Supervisorでサービスを起動
CMD ["/usr/bin/supervisord", "-c", "/etc/supervisor/conf.d/supervisord.conf"]
