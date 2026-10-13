#!/bin/sh
# web の起動(architecture.md 8.2)。マイグレーションは Railway のデプロイ前コマンドで行うので、ここではしない。
set -e

# Railway が渡す PORT で nginx が待ち受けるようにする(ローカルで試すときは 8080)
sed "s/__PORT__/${PORT:-8080}/" /etc/nginx/templates/default.conf > /etc/nginx/conf.d/default.conf

# 設定・ルート・画面を、本番の環境変数がそろった起動時にキャッシュする(ビルド時には環境変数がないため)
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache

exec supervisord -c /etc/supervisor/supervisord.conf
