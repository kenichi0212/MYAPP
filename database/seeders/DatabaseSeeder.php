<?php

namespace Database\Seeders;

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

        User::firstOrCreate(
            ['email' => 'demo@example.com'],
            ['name' => '開発用デモユーザー', 'password' => bcrypt('password')]
        );

        // 複数店舗・複数商品・複数履行のダミーデータはローカル開発でのみ投入する
        // （テスト実行時間への影響を避けるため）。
        if (app()->environment('local')) {
            $this->call(DevDataSeeder::class);
        }
    }
}
