<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * タスク4-8：商品情報取得ロジック（SPEC.md 4.1）の3分岐をエンドツーエンドで網羅する。
 */
class ProductInformationRetrievalTest extends TestCase
{
    use RefreshDatabase;

    public static function janCodeMatchScenarios(): array
    {
        return [
            '自社マスタに存在する' => ['existsInMaster', true, 'master'],
            '自社マスタには無いが外部APIに存在する' => ['existsInApiOnly', true, 'api'],
            'どちらにも存在しない' => ['existsNowhere', false, 'manual'],
        ];
    }

    #[DataProvider('janCodeMatchScenarios')]
    public function test_lookup_endpoint_returns_expected_name_source(
        string $scenario,
        bool $expectedFound,
        string $expectedNameSource
    ): void {
        $user = User::factory()->create();
        $janCode = '4901234567894';

        match ($scenario) {
            'existsInMaster' => Product::factory()->create([
                'company_id' => $user->company_id,
                'jan_code' => $janCode,
                'product_name' => '自社マスタ商品',
            ]),
            'existsInApiOnly' => Http::fake([
                "*/api/v2/product/{$janCode}.json" => Http::response([
                    'status' => 1,
                    'product' => ['product_name' => '外部API商品', 'brands' => '外部メーカー'],
                ]),
            ]),
            'existsNowhere' => Http::fake([
                "*/api/v2/product/{$janCode}.json" => Http::response(['status' => 0]),
            ]),
        };

        $this->actingAs($user)
            ->getJson("/api/products/lookup?jan_code={$janCode}")
            ->assertOk()
            ->assertJson([
                'found' => $expectedFound,
                'product' => ['name_source' => $expectedNameSource],
            ]);
    }

    #[DataProvider('janCodeMatchScenarios')]
    public function test_confirmation_screen_displays_expected_source_label(
        string $scenario,
        bool $expectedFound,
        string $expectedNameSource
    ): void {
        $user = User::factory()->create();
        $janCode = '4901234567894';

        match ($scenario) {
            'existsInMaster' => Product::factory()->create([
                'company_id' => $user->company_id,
                'jan_code' => $janCode,
                'product_name' => '自社マスタ商品',
            ]),
            'existsInApiOnly' => Http::fake([
                "*/api/v2/product/{$janCode}.json" => Http::response([
                    'status' => 1,
                    'product' => ['product_name' => '外部API商品', 'brands' => '外部メーカー'],
                ]),
            ]),
            'existsNowhere' => Http::fake([
                "*/api/v2/product/{$janCode}.json" => Http::response(['status' => 0]),
            ]),
        };

        $expectedLabel = match ($expectedNameSource) {
            'master' => '自社マスタ',
            'api' => '外部API取得',
            default => '手入力',
        };

        $this->actingAs($user)
            ->get("/products/confirm?jan_code={$janCode}")
            ->assertOk()
            ->assertSee($expectedLabel);
    }
}
