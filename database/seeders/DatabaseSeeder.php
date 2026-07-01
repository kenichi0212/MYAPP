<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\Store;
use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call(CompanySeeder::class);

        // hq_staff：全店舗が見える開発用ユーザー
        User::updateOrCreate(
            ['email' => 'demo@example.com'],
            ['name' => '開発用デモユーザー', 'password' => bcrypt('password'), 'role' => UserRole::HqStaff]
        );

        User::updateOrCreate(
            ['email' => 'admin@example.com'],
            ['name' => '開発用管理者', 'password' => bcrypt('password'), 'role' => UserRole::Admin]
        );

        // 複数店舗・複数商品・複数履行のダミーデータはローカル開発でのみ投入する
        // （テスト実行時間への影響を避けるため）。
        if (app()->environment('local')) {
            $this->call(DevDataSeeder::class);

            // store_staff ロールの動作確認用ユーザーを1店舗に紐付けて作成
            $firstStore = Store::first();
            if ($firstStore) {
                User::updateOrCreate(
                    ['email' => 'staff@example.com'],
                    [
                        'name'     => '開発用店舗担当者',
                        'password' => bcrypt('password'),
                        'role'     => UserRole::StoreStaff,
                        'store_id' => $firstStore->id,
                    ]
                );
            }
        }
    }
}
