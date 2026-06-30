<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\ExpiryCheckLog;
use App\Models\Product;
use App\Models\Store;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get('/dashboard')->assertRedirect(route('login'));
    }

    public function test_authenticated_user_can_access_dashboard(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->get('/dashboard')->assertOk();
    }

    public function test_dashboard_shows_needs_attention_count(): void
    {
        $store = Store::factory()->create();
        $user = User::factory()->create(['company_id' => $store->company_id]);
        $product = Product::factory()->create(['company_id' => $store->company_id]);

        // 要確認ロット: 期限3ヶ月以内 + 先月チェック
        ExpiryCheckLog::factory()->create([
            'company_id'  => $store->company_id,
            'product_id'  => $product->id,
            'store_id'    => $store->id,
            'expiry_date' => now()->addMonths(2)->toDateString(),
            'checked_by'  => $user->id,
            'checked_at'  => now()->subMonth(),
        ]);

        $this->actingAs($user)
            ->get('/dashboard')
            ->assertOk()
            ->assertSeeText('1'); // needsAttentionCount = 1
    }

    public function test_dashboard_shows_expiring_within_1_month_count(): void
    {
        $store = Store::factory()->create();
        $user = User::factory()->create(['company_id' => $store->company_id]);
        $product = Product::factory()->create(['company_id' => $store->company_id]);

        // 1ヶ月以内に期限切れ
        ExpiryCheckLog::factory()->create([
            'company_id'  => $store->company_id,
            'product_id'  => $product->id,
            'store_id'    => $store->id,
            'expiry_date' => now()->addWeeks(2)->toDateString(),
            'checked_by'  => $user->id,
            'checked_at'  => now(),
        ]);

        $this->actingAs($user)
            ->get('/dashboard')
            ->assertOk()
            ->assertSeeText('1'); // expiringWithin1MonthCount = 1
    }

    public function test_dashboard_shows_csv_import_link_for_hq_staff(): void
    {
        $user = User::factory()->create(['role' => UserRole::HqStaff]);

        $this->actingAs($user)
            ->get('/dashboard')
            ->assertOk()
            ->assertSeeText('CSV取込');
    }

    public function test_dashboard_hides_csv_import_link_for_store_staff(): void
    {
        $user = User::factory()->create(['role' => UserRole::StoreStaff]);

        $this->actingAs($user)
            ->get('/dashboard')
            ->assertOk()
            ->assertDontSeeText('CSV取込');
    }
}
