#!/bin/sh
set -e

php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan migrate --force

# 企業レコードを初回のみ作成
php artisan db:seed --class=CompanySeeder

# 管理者ユーザーを環境変数から初回のみ作成
if [ -n "$ADMIN_EMAIL" ] && [ -n "$ADMIN_PASSWORD" ]; then
    php artisan tinker --execute="
        \$company = \App\Models\Company::first();
        if (\$company) {
            \App\Models\User::firstOrCreate(
                ['email' => '$ADMIN_EMAIL'],
                [
                    'company_id' => \$company->id,
                    'name'       => '管理者',
                    'password'   => bcrypt('$ADMIN_PASSWORD'),
                    'role'       => \App\Enums\UserRole::Admin,
                    'is_active'  => true,
                ]
            );
        }
    "
fi

php artisan serve --host=0.0.0.0 --port="${PORT:-10000}"
