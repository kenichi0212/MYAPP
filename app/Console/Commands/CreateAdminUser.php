<?php

namespace App\Console\Commands;

use App\Enums\UserRole;
use App\Models\Company;
use App\Models\User;
use Illuminate\Console\Command;

class CreateAdminUser extends Command
{
    protected $signature   = 'app:create-admin';
    protected $description = '環境変数 ADMIN_EMAIL / ADMIN_PASSWORD から管理者ユーザーを作成する';

    public function handle(): int
    {
        $email    = env('ADMIN_EMAIL');
        $password = env('ADMIN_PASSWORD');

        if (! $email || ! $password) {
            $this->info('ADMIN_EMAIL / ADMIN_PASSWORD が未設定のためスキップします。');
            return self::SUCCESS;
        }

        $company = Company::first();
        if (! $company) {
            $this->error('企業レコードが存在しません。先に CompanySeeder を実行してください。');
            return self::FAILURE;
        }

        User::firstOrCreate(
            ['email' => $email],
            [
                'company_id' => $company->id,
                'name'       => '管理者',
                'password'   => bcrypt($password),
                'role'       => UserRole::Admin,
                'is_active'  => true,
            ]
        );

        $this->info("管理者ユーザー [{$email}] を作成しました（既存の場合はスキップ）。");
        return self::SUCCESS;
    }
}
