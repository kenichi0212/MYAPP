<?php

namespace Tests\Feature\Csv;

use App\Models\Company;
use App\Models\Product;
use App\Services\Csv\ProductMatcher;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductMatcherTest extends TestCase
{
    use RefreshDatabase;

    public function test_matches_by_internal_product_code_first(): void
    {
        $company = Company::first();
        $existing = Product::factory()->create([
            'company_id' => $company->id,
            'internal_product_code' => 'P001',
            'jan_code' => '4912345678904',
            'product_name' => '旧商品名',
        ]);
        $matcher = new ProductMatcher();

        $product = $matcher->match($company->id, 'P001', '9999999999999', '新商品名', 'メーカーB');

        $this->assertSame($existing->id, $product->id);
        $this->assertDatabaseHas('products', [
            'id' => $existing->id,
            'product_name' => '新商品名',
            'maker_name' => 'メーカーB',
        ]);
        $this->assertDatabaseCount('products', 1);
    }

    public function test_falls_back_to_jan_code_when_internal_code_not_found(): void
    {
        $company = Company::first();
        $existing = Product::factory()->create([
            'company_id' => $company->id,
            'internal_product_code' => null,
            'jan_code' => '4912345678904',
            'product_name' => '旧商品名',
        ]);
        $matcher = new ProductMatcher();

        // 自社商品コードが指定されているがマスタには存在しない -> JANコードでフォールバック
        $product = $matcher->match($company->id, 'P999', '4912345678904', '新商品名', null);

        $this->assertSame($existing->id, $product->id);
        $this->assertDatabaseHas('products', [
            'id' => $existing->id,
            'internal_product_code' => 'P999',
            'product_name' => '新商品名',
        ]);
        $this->assertDatabaseCount('products', 1);
    }

    public function test_matches_by_jan_code_when_internal_code_absent(): void
    {
        $company = Company::first();
        $existing = Product::factory()->create([
            'company_id' => $company->id,
            'internal_product_code' => null,
            'jan_code' => '4912345678904',
            'product_name' => '旧商品名',
        ]);
        $matcher = new ProductMatcher();

        $product = $matcher->match($company->id, null, '4912345678904', '新商品名', null);

        $this->assertSame($existing->id, $product->id);
        $this->assertDatabaseCount('products', 1);
    }

    public function test_creates_new_product_when_no_match_found(): void
    {
        $company = Company::first();
        $matcher = new ProductMatcher();

        $product = $matcher->match($company->id, 'P001', '4912345678904', '新商品', 'メーカーA');

        $this->assertDatabaseHas('products', [
            'id' => $product->id,
            'company_id' => $company->id,
            'internal_product_code' => 'P001',
            'jan_code' => '4912345678904',
            'product_name' => '新商品',
            'maker_name' => 'メーカーA',
            'name_source' => 'master',
        ]);
    }

    public function test_creates_new_product_when_only_jan_code_given_and_no_match(): void
    {
        $company = Company::first();
        $matcher = new ProductMatcher();

        $product = $matcher->match($company->id, null, '4912345678904', '新商品', null);

        $this->assertDatabaseHas('products', [
            'id' => $product->id,
            'internal_product_code' => null,
            'jan_code' => '4912345678904',
        ]);
    }

    public function test_matching_is_scoped_to_company(): void
    {
        $company = Company::first();
        $otherCompany = \App\Models\Company::create(['name' => '他社']);
        Product::factory()->create([
            'company_id' => $otherCompany->id,
            'internal_product_code' => 'P001',
            'product_name' => '他社の商品',
        ]);
        $matcher = new ProductMatcher();

        $product = $matcher->match($company->id, 'P001', null, '自社の商品', null);

        $this->assertNotEquals($otherCompany->id, $product->company_id);
        $this->assertSame($company->id, $product->company_id);
        $this->assertDatabaseCount('products', 2);
    }
}
