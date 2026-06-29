<?php

namespace App\Services\ProductLookup;

use App\Models\Product;
use App\Services\OpenFoodFacts\OpenFoodFactsClient;

class ProductLookupService
{
    public function __construct(private OpenFoodFactsClient $openFoodFactsClient)
    {
    }

    /**
     * JANコードから商品情報を取得する（SPEC.md 4.1：自社マスタ優先→Open Food Facts APIフォールバック）。
     *
     * @return array{found: bool, product_name: ?string, maker_name: ?string, jan_code: string, name_source: string}
     */
    public function lookup(int $companyId, string $janCode): array
    {
        $product = Product::where('company_id', $companyId)
            ->where('jan_code', $janCode)
            ->first();

        if ($product) {
            return [
                'found' => true,
                'product_name' => $product->product_name,
                'maker_name' => $product->maker_name,
                'jan_code' => $janCode,
                'name_source' => 'master',
            ];
        }

        $apiResult = $this->openFoodFactsClient->lookup($janCode);

        if ($apiResult) {
            return [
                'found' => true,
                'product_name' => $apiResult['product_name'],
                'maker_name' => $apiResult['maker_name'],
                'jan_code' => $janCode,
                'name_source' => 'api',
            ];
        }

        return [
            'found' => false,
            'product_name' => null,
            'maker_name' => null,
            'jan_code' => $janCode,
            'name_source' => 'manual',
        ];
    }
}
