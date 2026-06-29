<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class ProductLookupTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get('/api/products/lookup?jan_code=4901234567894')
            ->assertRedirect(route('login'));
    }

    public function test_returns_product_when_jan_code_exists_in_own_master(): void
    {
        $user = User::factory()->create();
        $product = Product::factory()->create([
            'company_id' => $user->company_id,
            'jan_code' => '4901234567894',
            'product_name' => 'テスト商品',
            'maker_name' => 'テストメーカー',
            'name_source' => 'manual',
        ]);

        $this->actingAs($user)
            ->getJson('/api/products/lookup?jan_code=4901234567894')
            ->assertOk()
            ->assertJson([
                'found' => true,
                'product' => [
                    'product_name' => 'テスト商品',
                    'maker_name' => 'テストメーカー',
                    'jan_code' => '4901234567894',
                    'name_source' => 'master',
                ],
            ]);
    }

    public function test_falls_back_to_external_api_when_jan_code_does_not_exist_in_master(): void
    {
        Http::fake([
            '*/api/v2/product/4901234567894.json' => Http::response([
                'status' => 1,
                'product' => ['product_name' => '外部API商品', 'brands' => '外部メーカー'],
            ]),
        ]);

        $user = User::factory()->create();

        $this->actingAs($user)
            ->getJson('/api/products/lookup?jan_code=4901234567894')
            ->assertOk()
            ->assertJson([
                'found' => true,
                'product' => [
                    'product_name' => '外部API商品',
                    'maker_name' => '外部メーカー',
                    'jan_code' => '4901234567894',
                    'name_source' => 'api',
                ],
            ]);
    }

    public function test_returns_not_found_when_neither_master_nor_external_api_has_the_jan_code(): void
    {
        Http::fake([
            '*/api/v2/product/4901234567894.json' => Http::response(['status' => 0]),
        ]);

        $user = User::factory()->create();

        $this->actingAs($user)
            ->getJson('/api/products/lookup?jan_code=4901234567894')
            ->assertOk()
            ->assertJson(['found' => false]);
    }

    public function test_does_not_match_products_belonging_to_another_company(): void
    {
        Http::fake([
            '*/api/v2/product/4901234567894.json' => Http::response(['status' => 0]),
        ]);

        $user = User::factory()->create();
        $otherCompany = Company::create(['name' => '他社株式会社']);
        Product::factory()->create([
            'company_id' => $otherCompany->id,
            'jan_code' => '4901234567894',
        ]);

        $this->actingAs($user)
            ->getJson('/api/products/lookup?jan_code=4901234567894')
            ->assertOk()
            ->assertJson(['found' => false]);
    }

    public function test_invalid_jan_code_format_is_rejected(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->getJson('/api/products/lookup?jan_code=12345')
            ->assertStatus(422);
    }
}
