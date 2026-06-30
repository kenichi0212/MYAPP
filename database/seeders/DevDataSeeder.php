<?php

namespace Database\Seeders;

use App\Models\Company;
use App\Models\ExpiryCheckLog;
use App\Models\Product;
use App\Models\ProductStoreAssignment;
use App\Models\StaffMember;
use App\Models\Store;
use App\Models\StoreGroup;
use App\Models\User;
use Illuminate\Database\Seeder;

class DevDataSeeder extends Seeder
{
    // 性能確認用スケール（SPEC.md 14.1：約40店舗 × 100品番 = 4,000ロット）
    const STORE_COUNT = 40;
    const PRODUCT_COUNT = 100;

    /**
     * 開発用に複数店舗・複数商品・複数履行（チェック履歴）を含むダミーデータを投入する。
     * SPEC.md 14.1 の想定規模（40店舗 × 100品番）で生成する。
     */
    public function run(): void
    {
        $company = Company::first();
        $checkedBy = User::first();

        $storeGroups = StoreGroup::factory()->count(4)->create();

        $stores = collect();
        foreach ($storeGroups as $i => $group) {
            $stores = $stores->concat(
                Store::factory()->count((int) ceil(self::STORE_COUNT / 4))->create([
                    'store_group_id' => $group->id,
                ])
            );
        }
        $stores = $stores->take(self::STORE_COUNT);

        StaffMember::factory()->count(10)->create();

        $products = Product::factory()->count(self::PRODUCT_COUNT)->create();

        // 全商品 × 全店舗 で lot を生成（= 4,000ロット）
        // insert を一括化してシード時間を短縮する
        $assignmentRows = [];
        $logRows = [];
        $now = now();

        foreach ($products as $product) {
            foreach ($stores as $store) {
                $assignmentRows[] = [
                    'company_id' => $company->id,
                    'product_id' => $product->id,
                    'store_id'   => $store->id,
                    'created_at' => $now,
                ];

                $expiryDate = $now->copy()->addMonths(random_int(0, 6))->endOfMonth()->toDateString();
                $checkCount = random_int(1, 3);

                for ($i = 0; $i < $checkCount; $i++) {
                    $isLastCheck = $i === $checkCount - 1;
                    $isZeroReport = $isLastCheck && random_int(1, 10) === 1;

                    $logRows[] = [
                        'company_id'    => $company->id,
                        'product_id'    => $product->id,
                        'store_id'      => $store->id,
                        'expiry_date'   => $expiryDate,
                        'quantity'      => $isZeroReport ? 0 : random_int(1, 50),
                        'is_zero_report' => $isZeroReport,
                        'data_source'   => 'master',
                        'checked_by'    => $checkedBy->id,
                        'checked_at'    => $now->copy()->subMonths($checkCount - $i - 1)->toDateTimeString(),
                    ];
                }
            }
        }

        // 500件ずつバルクインサート
        foreach (array_chunk($assignmentRows, 500) as $chunk) {
            ProductStoreAssignment::insert($chunk);
        }
        foreach (array_chunk($logRows, 500) as $chunk) {
            ExpiryCheckLog::insert($chunk);
        }
    }
}
