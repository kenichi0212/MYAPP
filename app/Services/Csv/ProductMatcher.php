<?php

namespace App\Services\Csv;

use App\Models\Product;

class ProductMatcher
{
    /**
     * 自社商品コード優先→JANコードフォールバック→新規登録、の優先順位で商品をマッチングし、
     * 見つかった場合は属性を更新する（SPEC.md 10.1・9.2）。
     */
    public function match(
        int $companyId,
        ?string $internalProductCode,
        ?string $janCode,
        string $productName,
        ?string $makerName,
        string $nameSourceForNewProduct = 'master',
    ): Product {
        $product = $this->findByInternalProductCode($companyId, $internalProductCode)
            ?? $this->findByJanCode($companyId, $janCode);

        $isNewProduct = $product === null;
        $product ??= new Product(['company_id' => $companyId]);

        $product->internal_product_code = $internalProductCode;
        $product->jan_code = $janCode;
        $product->product_name = $productName;
        $product->maker_name = $makerName;

        if ($isNewProduct) {
            $product->name_source = $nameSourceForNewProduct;
        }

        $product->save();

        return $product;
    }

    private function findByInternalProductCode(int $companyId, ?string $internalProductCode): ?Product
    {
        if (empty($internalProductCode)) {
            return null;
        }

        return Product::where('company_id', $companyId)
            ->where('internal_product_code', $internalProductCode)
            ->first();
    }

    private function findByJanCode(int $companyId, ?string $janCode): ?Product
    {
        if (empty($janCode)) {
            return null;
        }

        return Product::where('company_id', $companyId)
            ->where('jan_code', $janCode)
            ->first();
    }
}
