#!/bin/sh
# worker の起動(architecture.md 8.1)。採点 Job を処理する。
set -e

php artisan config:cache

# 1時間ごとに worker を入れ替えて、メモリが増え続けるのを防ぐ(終了すると Railway が立ち上げ直す)
exec php artisan queue:work --sleep=3 --max-time=3600
