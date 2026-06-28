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
    /**
     * 開発用に複数店舗・複数商品・複数履行（チェック履歴）を含むダミーデータを投入する。
     */
    public function run(): void
    {
        $company = Company::first();
        $checkedBy = User::first();

        $storeGroups = StoreGroup::factory()->count(2)->create();

        $stores = collect()
            ->concat(Store::factory()->count(4)->create(['store_group_id' => $storeGroups[0]->id]))
            ->concat(Store::factory()->count(4)->create(['store_group_id' => $storeGroups[1]->id]));

        StaffMember::factory()->count(5)->create();

        $products = Product::factory()->count(20)->create();

        foreach ($products as $product) {
            $assignedStores = $stores->random(random_int(2, 4));

            foreach ($assignedStores as $store) {
                ProductStoreAssignment::create([
                    'company_id' => $company->id,
                    'product_id' => $product->id,
                    'store_id' => $store->id,
                ]);

                // ロット（商品×店舗×賞味期限）ごとに、月次チェックを2〜3回分追記する。
                $expiryDate = now()->addMonths(random_int(-1, 6))->endOfMonth();
                $checkCount = random_int(1, 3);

                for ($i = 0; $i < $checkCount; $i++) {
                    $isLastCheck = $i === $checkCount - 1;
                    $isZeroReport = $isLastCheck && random_int(1, 10) === 1;

                    ExpiryCheckLog::create([
                        'company_id' => $company->id,
                        'product_id' => $product->id,
                        'store_id' => $store->id,
                        'expiry_date' => $expiryDate->toDateString(),
                        'quantity' => $isZeroReport ? 0 : random_int(1, 50),
                        'is_zero_report' => $isZeroReport,
                        'data_source' => 'master',
                        'checked_by' => $checkedBy->id,
                        'checked_at' => now()->subMonths($checkCount - $i - 1),
                    ]);
                }
            }
        }
    }
}
