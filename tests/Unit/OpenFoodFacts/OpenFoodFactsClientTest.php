<?php

namespace Tests\Unit\OpenFoodFacts;

use App\Services\OpenFoodFacts\OpenFoodFactsClient;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class OpenFoodFactsClientTest extends TestCase
{
    public function test_returns_product_info_on_successful_lookup(): void
    {
        Http::fake([
            '*/api/v2/product/4901234567894.json' => Http::response([
                'status' => 1,
                'product' => [
                    'product_name' => 'テスト商品',
                    'brands' => 'テストメーカー',
                ],
            ]),
        ]);

        $result = (new OpenFoodFactsClient())->lookup('4901234567894');

        $this->assertSame([
            'product_name' => 'テスト商品',
            'maker_name' => 'テストメーカー',
        ], $result);
    }

    public function test_returns_null_when_status_indicates_not_found(): void
    {
        Http::fake([
            '*/api/v2/product/0000000000000.json' => Http::response(['status' => 0]),
        ]);

        $this->assertNull((new OpenFoodFactsClient())->lookup('0000000000000'));
    }

    public function test_returns_null_when_product_name_is_missing(): void
    {
        Http::fake([
            '*/api/v2/product/4901234567894.json' => Http::response([
                'status' => 1,
                'product' => ['brands' => 'テストメーカー'],
            ]),
        ]);

        $this->assertNull((new OpenFoodFactsClient())->lookup('4901234567894'));
    }

    public function test_returns_null_on_server_error(): void
    {
        Http::fake([
            '*/api/v2/product/4901234567894.json' => Http::response('', 500),
        ]);

        $this->assertNull((new OpenFoodFactsClient())->lookup('4901234567894'));
    }

    public function test_returns_null_on_timeout(): void
    {
        Http::fake([
            '*/api/v2/product/4901234567894.json' => function () {
                throw new \Illuminate\Http\Client\ConnectionException('timed out');
            },
        ]);

        $this->assertNull((new OpenFoodFactsClient())->lookup('4901234567894'));
    }

    public function test_second_lookup_for_same_jan_code_does_not_call_api_again(): void
    {
        Http::fake([
            '*/api/v2/product/4901234567894.json' => Http::response([
                'status' => 1,
                'product' => ['product_name' => 'テスト商品', 'brands' => 'テストメーカー'],
            ]),
        ]);

        $client = new OpenFoodFactsClient();
        $client->lookup('4901234567894');
        $client->lookup('4901234567894');

        Http::assertSentCount(1);
    }

    public function test_not_found_result_is_also_cached_to_suppress_repeated_calls(): void
    {
        Http::fake([
            '*/api/v2/product/0000000000000.json' => Http::response(['status' => 0]),
        ]);

        $client = new OpenFoodFactsClient();
        $client->lookup('0000000000000');
        $result = $client->lookup('0000000000000');

        $this->assertNull($result);
        Http::assertSentCount(1);
    }
}
