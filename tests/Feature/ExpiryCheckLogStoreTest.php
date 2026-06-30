<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\Company;
use App\Models\ExpiryCheckLog;
use App\Models\Product;
use App\Models\Store;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExpiryCheckLogStoreTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_to_login(): void
    {
        $this->post('/api/check-logs', [])->assertRedirect(route('login'));
    }

    public function test_store_staff_can_register_a_check_log_for_their_own_store(): void
    {
        $store = Store::factory()->create();
        $user = User::factory()->create([
            'company_id' => $store->company_id,
            'role' => UserRole::StoreStaff,
            'store_id' => $store->id,
        ]);

        $response = $this->actingAs($user)->postJson('/api/check-logs', [
            'store_id' => $store->id,
            'jan_code' => '4901234567894',
            'product_name' => 'テスト商品',
            'maker_name' => 'テストメーカー',
            'name_source' => 'manual',
            'expiry_date' => now()->addMonth()->toDateString(),
            'quantity' => 5,
            'is_zero_report' => false,
        ]);

        $response->assertCreated();

        $this->assertDatabaseHas('expiry_check_logs', [
            'company_id' => $store->company_id,
            'store_id' => $store->id,
            'quantity' => 5,
            'is_zero_report' => false,
            'data_source' => 'manual',
            'checked_by' => $user->id,
        ]);
        $this->assertDatabaseHas('products', [
            'company_id' => $store->company_id,
            'jan_code' => '4901234567894',
            'product_name' => 'テスト商品',
        ]);
    }

    public function test_store_staff_cannot_register_for_another_store(): void
    {
        $store = Store::factory()->create();
        $otherStore = Store::factory()->create(['company_id' => $store->company_id]);
        $user = User::factory()->create([
            'company_id' => $store->company_id,
            'role' => UserRole::StoreStaff,
            'store_id' => $store->id,
        ]);

        $this->actingAs($user)->postJson('/api/check-logs', [
            'store_id' => $otherStore->id,
            'jan_code' => '4901234567894',
            'product_name' => 'テスト商品',
            'name_source' => 'manual',
            'expiry_date' => now()->addMonth()->toDateString(),
            'quantity' => 1,
        ])->assertForbidden();

        $this->assertDatabaseCount('expiry_check_logs', 0);
    }

    public function test_hq_staff_can_register_for_any_store_in_their_company(): void
    {
        $store = Store::factory()->create();
        $user = User::factory()->create([
            'company_id' => $store->company_id,
            'role' => UserRole::HqStaff,
            'store_id' => null,
        ]);

        $this->actingAs($user)->postJson('/api/check-logs', [
            'store_id' => $store->id,
            'jan_code' => '4901234567894',
            'product_name' => 'テスト商品',
            'name_source' => 'manual',
            'expiry_date' => now()->addMonth()->toDateString(),
            'quantity' => 1,
        ])->assertCreated();
    }

    public function test_cannot_register_for_a_store_belonging_to_another_company(): void
    {
        $store = Store::factory()->create();
        $otherCompany = Company::create(['name' => '他社株式会社']);
        $otherStore = Store::factory()->create(['company_id' => $otherCompany->id]);
        $user = User::factory()->create([
            'company_id' => $store->company_id,
            'role' => UserRole::Admin,
        ]);

        $this->actingAs($user)->postJson('/api/check-logs', [
            'store_id' => $otherStore->id,
            'jan_code' => '4901234567894',
            'product_name' => 'テスト商品',
            'name_source' => 'manual',
            'expiry_date' => now()->addMonth()->toDateString(),
            'quantity' => 1,
        ])->assertForbidden();

        $this->assertDatabaseCount('expiry_check_logs', 0);
    }

    public function test_past_expiry_date_is_rejected(): void
    {
        $store = Store::factory()->create();
        $user = User::factory()->create([
            'company_id' => $store->company_id,
            'role' => UserRole::Admin,
        ]);

        $this->actingAs($user)->postJson('/api/check-logs', [
            'store_id' => $store->id,
            'jan_code' => '4901234567894',
            'product_name' => 'テスト商品',
            'name_source' => 'manual',
            'expiry_date' => now()->subDay()->toDateString(),
            'quantity' => 1,
        ])->assertStatus(422)
            ->assertJsonValidationErrors('expiry_date');

        $this->assertDatabaseCount('expiry_check_logs', 0);
    }

    public function test_zero_report_forces_quantity_to_zero_regardless_of_submitted_value(): void
    {
        $store = Store::factory()->create();
        $user = User::factory()->create([
            'company_id' => $store->company_id,
            'role' => UserRole::Admin,
        ]);

        $this->actingAs($user)->postJson('/api/check-logs', [
            'store_id' => $store->id,
            'jan_code' => '4901234567894',
            'product_name' => 'テスト商品',
            'name_source' => 'manual',
            'expiry_date' => now()->addMonth()->toDateString(),
            'quantity' => 99,
            'is_zero_report' => true,
        ])->assertCreated();

        $this->assertDatabaseHas('expiry_check_logs', [
            'store_id' => $store->id,
            'quantity' => 0,
            'is_zero_report' => true,
        ]);
    }

    public function test_returns_conflict_on_duplicate_lot_without_quantity_mode(): void
    {
        $store = Store::factory()->create();
        $user = User::factory()->create([
            'company_id' => $store->company_id,
            'role' => UserRole::Admin,
        ]);
        $payload = [
            'store_id' => $store->id,
            'jan_code' => '4901234567894',
            'product_name' => 'テスト商品',
            'name_source' => 'manual',
            'expiry_date' => now()->addMonth()->toDateString(),
            'quantity' => 3,
        ];

        $this->actingAs($user)->postJson('/api/check-logs', $payload)->assertCreated();

        $this->actingAs($user)->postJson('/api/check-logs', $payload)
            ->assertStatus(409)
            ->assertJsonFragment(['conflict' => true, 'existing_quantity' => 3]);

        $this->assertDatabaseCount('expiry_check_logs', 1);
    }

    public function test_separate_mode_appends_independent_entry_without_summing(): void
    {
        $store = Store::factory()->create();
        $user = User::factory()->create([
            'company_id' => $store->company_id,
            'role' => UserRole::Admin,
        ]);
        $payload = [
            'store_id' => $store->id,
            'jan_code' => '4901234567894',
            'product_name' => 'テスト商品',
            'name_source' => 'manual',
            'expiry_date' => now()->addMonth()->toDateString(),
            'quantity' => 3,
        ];

        $this->actingAs($user)->postJson('/api/check-logs', $payload)->assertCreated();
        $this->actingAs($user)->postJson('/api/check-logs', $payload + ['quantity_mode' => 'separate'])
            ->assertCreated();

        $this->assertDatabaseCount('expiry_check_logs', 2);
        $this->assertSame(1, Product::where('jan_code', '4901234567894')->count());
    }

    public function test_add_mode_inserts_with_summed_quantity(): void
    {
        $store = Store::factory()->create();
        $user = User::factory()->create([
            'company_id' => $store->company_id,
            'role' => UserRole::Admin,
        ]);
        $expiry = now()->addMonth()->toDateString();

        $this->actingAs($user)->postJson('/api/check-logs', [
            'store_id' => $store->id,
            'jan_code' => '4901234567894',
            'product_name' => 'テスト商品',
            'name_source' => 'manual',
            'expiry_date' => $expiry,
            'quantity' => 3,
        ])->assertCreated();

        $this->actingAs($user)->postJson('/api/check-logs', [
            'store_id' => $store->id,
            'jan_code' => '4901234567894',
            'product_name' => 'テスト商品',
            'name_source' => 'manual',
            'expiry_date' => $expiry,
            'quantity' => 5,
            'quantity_mode' => 'add',
        ])->assertCreated();

        $this->assertDatabaseCount('expiry_check_logs', 2);
        $this->assertDatabaseHas('expiry_check_logs', [
            'store_id' => $store->id,
            'expiry_date' => $expiry,
            'quantity' => 8,
        ]);
    }

    public function test_zero_report_bypasses_conflict_check_and_inserts_directly(): void
    {
        $store = Store::factory()->create();
        $user = User::factory()->create([
            'company_id' => $store->company_id,
            'role' => UserRole::Admin,
        ]);
        $payload = [
            'store_id' => $store->id,
            'jan_code' => '4901234567894',
            'product_name' => 'テスト商品',
            'name_source' => 'manual',
            'expiry_date' => now()->addMonth()->toDateString(),
            'quantity' => 3,
        ];

        $this->actingAs($user)->postJson('/api/check-logs', $payload)->assertCreated();

        // ゼロ登録は重複ダイアログをスキップして直接 INSERT される
        $this->actingAs($user)->postJson('/api/check-logs', $payload + [
            'quantity' => 0,
            'is_zero_report' => true,
        ])->assertCreated();

        $this->assertDatabaseCount('expiry_check_logs', 2);
        $this->assertDatabaseHas('expiry_check_logs', [
            'store_id' => $store->id,
            'quantity' => 0,
            'is_zero_report' => true,
        ]);
    }

    public function test_existing_master_product_is_reused_rather_than_duplicated(): void
    {
        $store = Store::factory()->create();
        $user = User::factory()->create([
            'company_id' => $store->company_id,
            'role' => UserRole::Admin,
        ]);
        $product = Product::factory()->create([
            'company_id' => $store->company_id,
            'jan_code' => '4901234567894',
            'product_name' => '既存商品',
        ]);

        $this->actingAs($user)->postJson('/api/check-logs', [
            'store_id' => $store->id,
            'jan_code' => '4901234567894',
            'product_name' => '既存商品',
            'name_source' => 'master',
            'expiry_date' => now()->addMonth()->toDateString(),
            'quantity' => 1,
        ])->assertCreated();

        $this->assertDatabaseCount('products', 1);
        $this->assertDatabaseHas('expiry_check_logs', [
            'product_id' => $product->id,
        ]);
    }
}
