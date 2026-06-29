<?php

namespace Database\Seeders;

use App\Models\Company;
use Illuminate\Database\Seeder;

class CompanySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // MVPでは1社固定運用のため、idを明示指定せず自動採番に任せる
        // （明示指定するとPostgresの自動採番シーケンスが追従せず、
        // 後続のCompany::create()でid重複エラーになる）。
        Company::firstOrCreate(
            ['name' => 'テスト株式会社']
        );
    }
}
