<?php

namespace Tests\Unit\ProductLookup;

use App\Models\Company;
use App\Models\Product;
use App\Services\ProductLookup\ProductLookupService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class ProductLookupServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_returns_master_hit_when_product_exists_in_own_company(): void
    {
        $company = Company::create(['name' => 'テスト株式会社']);
        Product::factory()->create([
            'company_id' => $company->id,
            'jan_code' => '4901234567894',
            'product_name' => '自社商品',
            'maker_name' => '自社メーカー',
        ]);

        $result = app(ProductLookupService::class)->lookup($company->id, '4901234567894');

        $this->assertSame([
            'found' => true,
            'product_name' => '自社商品',
            'maker_name' => '自社メーカー',
            'jan_code' => '4901234567894',
            'name_source' => 'master',
        ], $result);
    }

    public function test_falls_back_to_api_when_not_in_own_master(): void
    {
        $company = Company::create(['name' => 'テスト株式会社']);
        Http::fake([
            '*/api/v2/product/4901234567894.json' => Http::response([
                'status' => 1,
                'product' => ['product_name' => '外部API商品', 'brands' => '外部メーカー'],
            ]),
        ]);

        $result = app(ProductLookupService::class)->lookup($company->id, '4901234567894');

        $this->assertSame([
            'found' => true,
            'product_name' => '外部API商品',
            'maker_name' => '外部メーカー',
            'jan_code' => '4901234567894',
            'name_source' => 'api',
        ], $result);
    }

    public function test_returns_manual_when_neither_master_nor_api_has_a_match(): void
    {
        $company = Company::create(['name' => 'テスト株式会社']);
        Http::fake([
            '*/api/v2/product/4901234567894.json' => Http::response(['status' => 0]),
        ]);

        $result = app(ProductLookupService::class)->lookup($company->id, '4901234567894');

        $this->assertSame([
            'found' => false,
            'product_name' => null,
            'maker_name' => null,
            'jan_code' => '4901234567894',
            'name_source' => 'manual',
        ], $result);
    }

    public function test_does_not_match_another_companys_product(): void
    {
        $companyA = Company::create(['name' => 'A社']);
        $companyB = Company::create(['name' => 'B社']);
        Product::factory()->create(['company_id' => $companyB->id, 'jan_code' => '4901234567894']);

        Http::fake([
            '*/api/v2/product/4901234567894.json' => Http::response(['status' => 0]),
        ]);

        $result = app(ProductLookupService::class)->lookup($companyA->id, '4901234567894');

        $this->assertFalse($result['found']);
    }
}
