<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class ProductConfirmationPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get('/products/confirm?jan_code=4901234567894')
            ->assertRedirect(route('login'));
    }

    public function test_shows_master_source_label_when_product_exists_in_own_master(): void
    {
        $user = User::factory()->create();
        Product::factory()->create([
            'company_id' => $user->company_id,
            'jan_code' => '4901234567894',
            'product_name' => 'テスト商品',
            'maker_name' => 'テストメーカー',
        ]);

        $this->actingAs($user)
            ->get('/products/confirm?jan_code=4901234567894')
            ->assertOk()
            ->assertSee('自社マスタ')
            ->assertSee('テスト商品')
            ->assertSee('テストメーカー');
    }

    public function test_shows_api_source_label_when_only_external_api_has_a_match(): void
    {
        Http::fake([
            '*/api/v2/product/4901234567894.json' => Http::response([
                'status' => 1,
                'product' => ['product_name' => '外部API商品', 'brands' => '外部メーカー'],
            ]),
        ]);

        $user = User::factory()->create();

        $this->actingAs($user)
            ->get('/products/confirm?jan_code=4901234567894')
            ->assertOk()
            ->assertSee('外部API取得')
            ->assertSee('外部API商品');
    }

    public function test_shows_manual_label_when_neither_master_nor_api_has_a_match(): void
    {
        Http::fake([
            '*/api/v2/product/4901234567894.json' => Http::response(['status' => 0]),
        ]);

        $user = User::factory()->create();

        $this->actingAs($user)
            ->get('/products/confirm?jan_code=4901234567894')
            ->assertOk()
            ->assertSee('手入力')
            ->assertSee('見つかりませんでした');
    }
}
