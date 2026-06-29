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
            ->assertSeeHtml('value="テスト商品"')
            ->assertSeeHtml('value="テストメーカー"');
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
            ->assertSeeHtml('value="外部API商品"');
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
            ->assertSee('見つかりませんでした')
            ->assertSeeHtml('id="product-name-input"')
            ->assertSeeHtml('required');
    }

    public function test_maker_name_input_has_no_required_attribute(): void
    {
        $user = User::factory()->create();
        Product::factory()->create([
            'company_id' => $user->company_id,
            'jan_code' => '4901234567894',
            'maker_name' => null,
        ]);

        $content = $this->actingAs($user)
            ->get('/products/confirm?jan_code=4901234567894')
            ->assertOk()
            ->getContent();

        preg_match('/<input[^>]*id="maker-name-input"[^>]*>/', $content, $matches);

        $this->assertNotEmpty($matches);
        $this->assertStringNotContainsString('required', $matches[0]);
    }

    public function test_shows_expiry_date_and_quantity_and_zero_report_fields(): void
    {
        $user = User::factory()->create();
        Product::factory()->create([
            'company_id' => $user->company_id,
            'jan_code' => '4901234567894',
        ]);

        $this->actingAs($user)
            ->get('/products/confirm?jan_code=4901234567894')
            ->assertOk()
            ->assertSeeHtml('id="expiry-date-input"')
            ->assertSeeHtml('type="date"')
            ->assertSeeHtml('id="quantity-input"')
            ->assertSeeHtml('type="number"')
            ->assertSeeHtml('id="is-zero-report-input"')
            ->assertSee('売場に商品が無い');
    }

    public function test_expiry_date_input_disallows_past_dates_via_min_attribute(): void
    {
        $user = User::factory()->create();
        Product::factory()->create([
            'company_id' => $user->company_id,
            'jan_code' => '4901234567894',
        ]);

        $this->actingAs($user)
            ->get('/products/confirm?jan_code=4901234567894')
            ->assertOk()
            ->assertSeeHtml('min="'.now()->toDateString().'"')
            ->assertSeeHtml('id="expiry-date-error"')
            ->assertSee('賞味期限に過去の日付は登録できません');
    }
}
