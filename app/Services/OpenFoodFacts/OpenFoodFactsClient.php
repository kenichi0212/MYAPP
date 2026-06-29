<?php

namespace App\Services\OpenFoodFacts;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Throwable;

class OpenFoodFactsClient
{
    private const CACHE_KEY_PREFIX = 'open_food_facts:lookup:';

    /**
     * JANコードで商品情報を問い合わせる。該当なし・タイムアウト・APIエラーの場合はnullを返す
     * （SPEC.md 8.1：いずれの場合も呼び出し元で手入力を促す扱いにするため、エラー種別は区別しない）。
     *
     * 無料利用のレート制限を踏まえ、同一JANコードへの結果（該当なしも含む）を簡易キャッシュし、
     * 連続呼び出しを抑制する（自社マスタへの自動追加は行わず、一時保持のみ）。
     *
     * @return array{product_name: string, maker_name: ?string}|null
     */
    public function lookup(string $janCode): ?array
    {
        $cacheKey = self::CACHE_KEY_PREFIX.$janCode;

        if (Cache::has($cacheKey)) {
            return Cache::get($cacheKey)['result'];
        }

        $result = $this->fetch($janCode);

        Cache::put($cacheKey, ['result' => $result], (int) config('services.open_food_facts.cache_ttl'));

        return $result;
    }

    /**
     * @return array{product_name: string, maker_name: ?string}|null
     */
    private function fetch(string $janCode): ?array
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
