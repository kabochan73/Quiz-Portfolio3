# ローカル開発用の PHP イメージ。app(PHP-FPM)と worker(キューの処理)の両方で使う。
# 本番(Railway)用のイメージは docker/production/Dockerfile に別で作る(architecture.md 8.2)。
FROM php:8.5-fpm

# pdo_pgsql: PostgreSQL への接続
# intl: 日付・数値の整形
# zip / unzip / git: composer install で必要
RUN apt-get update \
    && apt-get install -y --no-install-recommends git unzip libpq-dev libicu-dev libzip-dev \
    && docker-php-ext-install pdo_pgsql intl zip \
    && rm -rf /var/lib/apt/lists/*

# ローカルに Composer を入れなくてよいよう、公式イメージからバイナリだけコピーする
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html
