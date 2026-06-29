<?php

namespace App\Services\OpenFoodFacts;

use Illuminate\Support\Facades\Http;
use Throwable;

class OpenFoodFactsClient
{
    /**
     * JANコードで商品情報を問い合わせる。該当なし・タイムアウト・APIエラーの場合はnullを返す
     * （SPEC.md 8.1：いずれの場合も呼び出し元で手入力を促す扱いにするため、エラー種別は区別しない）。
     *
     * @return array{product_name: string, maker_name: ?string}|null
     */
    public function lookup(string $janCode): ?array
    {
        try {
            $response = Http::timeout((int) config('services.open_food_facts.timeout'))
                ->get(config('services.open_food_facts.base_url')."/api/v2/product/{$janCode}.json");
        } catch (Throwable) {
            return null;
        }

        if (! $response->successful()) {
            return null;
        }

        $body = $response->json();

        if (($body['status'] ?? 0) !== 1) {
            return null;
        }

        $productName = $body['product']['product_name'] ?? null;

        if (empty($productName)) {
            return null;
        }

        return [
            'product_name' => $productName,
            'maker_name' => $body['product']['brands'] ?? null,
        ];
    }
}
