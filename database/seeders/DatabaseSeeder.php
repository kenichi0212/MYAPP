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
    }
}
