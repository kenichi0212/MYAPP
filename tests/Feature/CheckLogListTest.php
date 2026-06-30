<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\ExpiryCheckLog;
use App\Models\Product;
use App\Models\Store;
use App\Models\User;
use App\Services\ExpiryCheck\ExpiryCheckListService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CheckLogListTest extends TestCase
{
    use RefreshDatabase;

    // ─────────────────────────────────────────────────────────
    // S05 画面アクセス
    // ─────────────────────────────────────────────────────────

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get('/check-logs')->assertRedirect(route('login'));
    }

    public function test_authenticated_user_can_access_list_page(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->get('/check-logs')->assertOk();
    }

    // ─────────────────────────────────────────────────────────
    // 現在の状態クエリ（SPEC.md 12.2）：最新チェック行のみ返す
    // ─────────────────────────────────────────────────────────

    public function test_list_shows_only_latest_log_per_lot(): void
    {
        // HTML ページは他の箇所に数字が出るため、API エンドポイントで確認する
        $store = Store::factory()->create();
        $product = Product::factory()->create(['company_id' => $store->company_id]);
        $user = User::factory()->create(['company_id' => $store->company_id]);
        $expiry = now()->addMonths(4)->toDateString();

        // 同一ロットに 2 回登録（古い順）
        ExpiryCheckLog::factory()->create([
            'company_id'  => $store->company_id,
            'product_id'  => $product->id,
            'store_id'    => $store->id,
            'expiry_date' => $expiry,
            'quantity'    => 10,
            'checked_by'  => $user->id,
            'checked_at'  => now()->subDays(10),
        ]);
        ExpiryCheckLog::factory()->create([
            'company_id'  => $store->company_id,
            'product_id'  => $product->id,
            'store_id'    => $store->id,
            'expiry_date' => $expiry,
            'quantity'    => 5,
            'checked_by'  => $user->id,
            'checked_at'  => now()->subDay(),
        ]);

        // API は最新の 1 件だけを返す（INSERT が 2 件あっても）
        $this->actingAs($user)
            ->getJson('/api/check-logs')
            ->assertOk()
            ->assertJsonCount(1)
            ->assertJsonFragment(['quantity' => 5]);
    }

    // ─────────────────────────────────────────────────────────
    // フィルタ（6-3・6-5）
    // ─────────────────────────────────────────────────────────

    public function test_filter_by_store_id_returns_only_that_stores_logs(): void
    {
        // フィルタドロップダウンには全店舗が出るため API で確認する
        $storeA = Store::factory()->create(['store_name' => 'A店舗']);
        $storeB = Store::factory()->create(['store_name' => 'B店舗', 'company_id' => $storeA->company_id]);
        $user = User::factory()->create(['company_id' => $storeA->company_id, 'role' => UserRole::HqStaff]);
        $productA = Product::factory()->create(['company_id' => $storeA->company_id]);
        $productB = Product::factory()->create(['company_id' => $storeA->company_id]);

        ExpiryCheckLog::factory()->create([
            'company_id' => $storeA->company_id,
            'product_id' => $productA->id,
            'store_id'   => $storeA->id,
            'checked_by' => $user->id,
            'checked_at' => now(),
        ]);
        ExpiryCheckLog::factory()->create([
            'company_id' => $storeA->company_id,
            'product_id' => $productB->id,
            'store_id'   => $storeB->id,
            'checked_by' => $user->id,
            'checked_at' => now(),
        ]);

        $this->actingAs($user)
            ->getJson("/api/check-logs?store_id={$storeA->id}")
            ->assertOk()
            ->assertJsonCount(1)
            ->assertJsonFragment(['store_id' => $storeA->id]);
    }

    public function test_filter_by_expiry_within_months(): void
    {
        $store = Store::factory()->create();
        $user = User::factory()->create(['company_id' => $store->company_id]);

        $nearProduct = Product::factory()->create([
            'company_id' => $store->company_id,
            'product_name' => '期限近い商品',
        ]);
        $farProduct = Product::factory()->create([
            'company_id' => $store->company_id,
            'product_name' => '期限遠い商品',
        ]);

        ExpiryCheckLog::factory()->create([
            'company_id'  => $store->company_id,
            'product_id'  => $nearProduct->id,
            'store_id'    => $store->id,
            'expiry_date' => now()->addWeeks(2)->toDateString(),
            'checked_by'  => $user->id,
            'checked_at'  => now(),
        ]);
        ExpiryCheckLog::factory()->create([
            'company_id'  => $store->company_id,
            'product_id'  => $farProduct->id,
            'store_id'    => $store->id,
            'expiry_date' => now()->addMonths(5)->toDateString(),
            'checked_by'  => $user->id,
            'checked_at'  => now(),
        ]);

        $this->actingAs($user)
            ->get('/check-logs?expiry_within=1')
            ->assertOk()
            ->assertSeeText('期限近い商品')
            ->assertDontSeeText('期限遠い商品');
    }

    // ─────────────────────────────────────────────────────────
    // 要確認判定（SPEC.md 12.3 の 4 パターン）
    // ─────────────────────────────────────────────────────────

    public function test_needs_attention_pattern1_no_attention_when_expiry_is_far(): void
    {
        // パターン1: 残り期間 >= 閾値（3ヶ月）→ 対象外
        $store = Store::factory()->create();
        $user = User::factory()->create(['company_id' => $store->company_id]);
        $product = Product::factory()->create(['company_id' => $store->company_id]);

        $log = ExpiryCheckLog::factory()->create([
            'company_id'  => $store->company_id,
            'product_id'  => $product->id,
            'store_id'    => $store->id,
            'expiry_date' => now()->addMonths(4)->toDateString(), // 4ヶ月後
            'checked_by'  => $user->id,
            'checked_at'  => now()->subMonths(2),                // 先々月
        ]);

        $service = app(ExpiryCheckListService::class);
        $service->attachNeedsAttention($log);

        $this->assertFalse($log->needs_attention);
    }

    public function test_needs_attention_pattern2_no_attention_when_checked_this_month(): void
    {
        // パターン2: 残り期間 < 3ヶ月 かつ 今月チェック済み → 対象外
        $store = Store::factory()->create();
        $user = User::factory()->create(['company_id' => $store->company_id]);
        $product = Product::factory()->create(['company_id' => $store->company_id]);

        $log = ExpiryCheckLog::factory()->create([
            'company_id'  => $store->company_id,
            'product_id'  => $product->id,
            'store_id'    => $store->id,
            'expiry_date' => now()->addMonths(2)->toDateString(), // 2ヶ月後
            'checked_by'  => $user->id,
            'checked_at'  => now(),                               // 今月チェック
        ]);

        $service = app(ExpiryCheckListService::class);
        $service->attachNeedsAttention($log);

        $this->assertFalse($log->needs_attention);
    }

    public function test_needs_attention_pattern4_attention_when_near_and_not_checked_this_month(): void
    {
        // パターン4: 残り期間 < 3ヶ月 かつ 今月未チェック → 要確認
        $store = Store::factory()->create();
        $user = User::factory()->create(['company_id' => $store->company_id]);
        $product = Product::factory()->create(['company_id' => $store->company_id]);

        $log = ExpiryCheckLog::factory()->create([
            'company_id'  => $store->company_id,
            'product_id'  => $product->id,
            'store_id'    => $store->id,
            'expiry_date' => now()->addMonths(2)->toDateString(), // 2ヶ月後
            'checked_by'  => $user->id,
            'checked_at'  => now()->subMonth(),                   // 先月チェック
        ]);

        $service = app(ExpiryCheckListService::class);
        $service->attachNeedsAttention($log);

        $this->assertTrue($log->needs_attention);
    }

    public function test_needs_attention_only_filter_excludes_non_attention_lots(): void
    {
        $store = Store::factory()->create();
        $user = User::factory()->create(['company_id' => $store->company_id]);

        $attentionProduct = Product::factory()->create([
            'company_id' => $store->company_id,
            'product_name' => '要確認商品',
        ]);
        $okProduct = Product::factory()->create([
            'company_id' => $store->company_id,
            'product_name' => '正常商品',
        ]);

        // 要確認: 期限近い + 先月チェック
        ExpiryCheckLog::factory()->create([
            'company_id'  => $store->company_id,
            'product_id'  => $attentionProduct->id,
            'store_id'    => $store->id,
            'expiry_date' => now()->addMonths(2)->toDateString(),
            'checked_by'  => $user->id,
            'checked_at'  => now()->subMonth(),
        ]);
        // 正常: 今月チェック済み
        ExpiryCheckLog::factory()->create([
            'company_id'  => $store->company_id,
            'product_id'  => $okProduct->id,
            'store_id'    => $store->id,
            'expiry_date' => now()->addMonths(2)->toDateString(),
            'checked_by'  => $user->id,
            'checked_at'  => now(),
        ]);

        $this->actingAs($user)
            ->get('/check-logs?needs_attention_only=1')
            ->assertOk()
            ->assertSeeText('要確認商品')
            ->assertDontSeeText('正常商品');
    }

    // ─────────────────────────────────────────────────────────
    // GET /api/check-logs（6-9）
    // ─────────────────────────────────────────────────────────

    public function test_api_check_logs_returns_current_state_as_json(): void
    {
        $store = Store::factory()->create();
        $user = User::factory()->create(['company_id' => $store->company_id]);
        $product = Product::factory()->create(['company_id' => $store->company_id]);
        $expiry = now()->addMonths(4)->toDateString();

        ExpiryCheckLog::factory()->create([
            'company_id'  => $store->company_id,
            'product_id'  => $product->id,
            'store_id'    => $store->id,
            'expiry_date' => $expiry,
            'quantity'    => 7,
            'checked_by'  => $user->id,
            'checked_at'  => now()->subDays(5),
        ]);
        ExpiryCheckLog::factory()->create([
            'company_id'  => $store->company_id,
            'product_id'  => $product->id,
            'store_id'    => $store->id,
            'expiry_date' => $expiry,
            'quantity'    => 3,
            'checked_by'  => $user->id,
            'checked_at'  => now(),
        ]);

        $this->actingAs($user)
            ->getJson('/api/check-logs')
            ->assertOk()
            ->assertJsonCount(1)
            ->assertJsonFragment(['quantity' => 3]);
    }

    // ─────────────────────────────────────────────────────────
    // GET /api/alerts/uncheck（6-10）
    // ─────────────────────────────────────────────────────────

    public function test_api_alerts_uncheck_returns_only_needs_attention_lots(): void
    {
        $store = Store::factory()->create();
        $user = User::factory()->create(['company_id' => $store->company_id]);

        $attentionProduct = Product::factory()->create(['company_id' => $store->company_id]);
        $okProduct = Product::factory()->create(['company_id' => $store->company_id]);

        ExpiryCheckLog::factory()->create([
            'company_id'  => $store->company_id,
            'product_id'  => $attentionProduct->id,
            'store_id'    => $store->id,
            'expiry_date' => now()->addMonths(2)->toDateString(),
            'checked_by'  => $user->id,
            'checked_at'  => now()->subMonth(),
        ]);
        ExpiryCheckLog::factory()->create([
            'company_id'  => $store->company_id,
            'product_id'  => $okProduct->id,
            'store_id'    => $store->id,
            'expiry_date' => now()->addMonths(2)->toDateString(),
            'checked_by'  => $user->id,
            'checked_at'  => now(),
        ]);

        $this->actingAs($user)
            ->getJson('/api/alerts/uncheck')
            ->assertOk()
            ->assertJsonCount(1)
            ->assertJsonFragment(['product_id' => $attentionProduct->id]);
    }
}
