# 本番(Railway)用のイメージ(architecture.md 8.2)。web と worker の両方で使い、起動のしかただけを変える。
#   web:    start-web.sh(既定)… nginx + PHP-FPM を supervisord でまとめて起動する
#   worker: start-worker.sh … 採点 Job を処理する
# ローカル開発用のイメージ(docker/php/Dockerfile)とは別にしている。

# ---- 1. PHP の依存パッケージ(本番に要らない開発用は入れない) ----
FROM php:8.5-cli AS vendor

RUN apt-get update \
    && apt-get install -y --no-install-recommends git unzip libicu-dev libzip-dev \
    && docker-php-ext-install intl zip \
    && rm -rf /var/lib/apt/lists/*
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /app
COPY composer.json composer.lock ./
# アプリのコードはまだないので、オートロードとスクリプトは最後のステージで作る
RUN composer install --no-dev --no-scripts --no-autoloader --prefer-dist --no-interaction --no-progress

# ---- 2. CSS / JS とフォントのビルド ----
FROM node:22-slim AS assets

WORKDIR /app
COPY package.json package-lock.json ./
RUN npm ci
COPY . .
# Tailwind が読むページ送りのテンプレートが vendor にあるので、1 の結果も置いておく
COPY --from=vendor /app/vendor ./vendor
RUN npm run build

# ---- 3. 実行用 ----
# OPcache は PHP 8.5 から本体に組み込まれているので、拡張として入れなくてよい(設定は php.ini)
FROM php:8.5-fpm

RUN apt-get update \
    && apt-get install -y --no-install-recommends nginx supervisor libpq-dev libicu-dev libzip-dev \
    && docker-php-ext-install pdo_pgsql intl zip \
    && rm -rf /var/lib/apt/lists/* /etc/nginx/sites-enabled/default
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

COPY docker/production/php.ini /usr/local/etc/php/conf.d/zz-production.ini
COPY docker/production/nginx.conf /etc/nginx/templates/default.conf
COPY docker/production/supervisord.conf /etc/supervisor/supervisord.conf
COPY docker/production/start-web.sh docker/production/start-worker.sh /usr/local/bin/

WORKDIR /var/www/html
COPY . .
COPY --from=vendor /app/vendor ./vendor
COPY --from=assets /app/public/build ./public/build

RUN composer dump-autoload --optimize --no-dev --classmap-authoritative \
    && php artisan package:discover --ansi \
    && chown -R www-data:www-data storage bootstrap/cache \
    && chmod +x /usr/local/bin/start-web.sh /usr/local/bin/start-worker.sh

CMD ["start-web.sh"]
