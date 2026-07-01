#!/bin/sh
set -e

php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan migrate --force

# 企業レコードを初回のみ作成
php artisan db:seed --class=CompanySeeder --force

# 管理者ユーザーを環境変数から初回のみ作成
php artisan app:create-admin

php artisan serve --host=0.0.0.0 --port="${PORT:-10000}"
