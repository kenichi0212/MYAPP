<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\Product;
use App\Models\ProductStoreAssignment;
use App\Models\Store;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class CsvImportFlowTest extends TestCase
{
    use RefreshDatabase;

    private function csvFile(string $contents, string $name = 'master.csv'): UploadedFile
    {
        $path = tempnam(sys_get_temp_dir(), 'csv');
        file_put_contents($path, $contents);

        return new UploadedFile($path, $name, 'text/csv', null, true);
    }

    public function test_preview_shows_success_and_error_counts(): void
    {
        $user = User::factory()->create(['role' => UserRole::Admin]);

        $csv = "メーカー名,商品名,店舗コード,店舗名,JANコード\n".
            "メーカーA,商品A,S001,店舗A,4912345678904\n".
            "メーカーB,商品B,,店舗B,4912345678905\n"; // 店舗コード欠落 -> エラー行

        $response = $this->actingAs($user)->post(route('csv-imports.preview'), [
            'file' => $this->csvFile($csv),
            'scope' => 'all_stores',
        ]);

        $response->assertOk();
        $response->assertSee('成功件数');
        $response->assertSee('1');
        $response->assertSee('店舗コードが入力されていません');
    }

    public function test_confirm_imports_valid_rows_and_records_batch_and_errors(): void
    {
        $user = User::factory()->create(['role' => UserRole::Admin]);

        $csv = "メーカー名,商品名,店舗コード,店舗名,JANコード\n".
            "メーカーA,商品A,S001,店舗A,4912345678904\n".
            "メーカーB,商品B,,店舗B,4912345678905\n";

        $this->actingAs($user)->post(route('csv-imports.preview'), [
            'file' => $this->csvFile($csv),
            'scope' => 'all_stores',
        ]);

        $response = $this->actingAs($user)->post(route('csv-imports.confirm'));

        $response->assertRedirect(route('csv-imports.create'));

        $this->assertDatabaseHas('stores', ['company_id' => $user->company_id, 'store_code' => 'S001', 'store_name' => '店舗A']);
        $this->assertDatabaseHas('products', ['company_id' => $user->company_id, 'jan_code' => '4912345678904', 'product_name' => '商品A']);
        $this->assertDatabaseHas('csv_import_batches', [
            'company_id' => $user->company_id,
            'total_rows' => 2,
            'success_count' => 1,
            'error_count' => 1,
        ]);
        $this->assertDatabaseHas('csv_import_errors', [
            'row_number' => 3,
            'error_reason' => '店舗コードが入力されていません',
        ]);

        $store = Store::where('store_code', 'S001')->first();
        $product = Product::where('jan_code', '4912345678904')->first();
        $this->assertDatabaseHas('product_store_assignments', [
            'store_id' => $store->id,
            'product_id' => $product->id,
        ]);
    }

    public function test_confirm_updates_existing_assignment_instead_of_duplicating(): void
    {
        $user = User::factory()->create(['role' => UserRole::Admin]);
        $store = Store::factory()->create(['company_id' => $user->company_id, 'store_code' => 'S001']);
        $product = Product::factory()->create(['company_id' => $user->company_id, 'jan_code' => '4912345678904']);
        ProductStoreAssignment::factory()->create([
            'company_id' => $user->company_id,
            'store_id' => $store->id,
            'product_id' => $product->id,
        ]);

        $csv = "メーカー名,商品名,店舗コード,店舗名,JANコード\n".
            "メーカーA,商品A,S001,店舗A,4912345678904\n";

        $this->actingAs($user)->post(route('csv-imports.preview'), [
            'file' => $this->csvFile($csv),
            'scope' => 'all_stores',
        ]);
        $this->actingAs($user)->post(route('csv-imports.confirm'));

        $this->assertDatabaseCount('product_store_assignments', 1);
    }

    public function test_store_staff_cannot_preview_or_confirm(): void
    {
        $user = User::factory()->create(['role' => UserRole::StoreStaff]);

        $response = $this->actingAs($user)->post(route('csv-imports.preview'), [
            'file' => $this->csvFile("店舗コード\nS001\n"),
            'scope' => 'all_stores',
        ]);

        $response->assertForbidden();
    }

    public function test_missing_required_header_is_rejected_before_row_processing(): void
    {
        $user = User::factory()->create(['role' => UserRole::Admin]);

        // 店舗コード列そのものがヘッダーに無い
        $csv = "メーカー名,商品名,JANコード\nメーカーA,商品A,4912345678904\n";

        $response = $this->actingAs($user)->post(route('csv-imports.preview'), [
            'file' => $this->csvFile($csv),
            'scope' => 'all_stores',
        ]);

        $response->assertSessionHasErrors('file');
    }

    public function test_preview_handles_shift_jis_encoded_file(): void
    {
        $user = User::factory()->create(['role' => UserRole::Admin]);

        $utf8 = "メーカー名,商品名,店舗コード,店舗名,JANコード\nメーカー製菓,賞味期限テスト商品,S001,新宿店,4912345678904\n";
        $sjis = mb_convert_encoding($utf8, 'SJIS-win', 'UTF-8');

        $response = $this->actingAs($user)->post(route('csv-imports.preview'), [
            'file' => $this->csvFile($sjis),
            'scope' => 'all_stores',
        ]);

        $response->assertOk();
        $response->assertSee('賞味期限テスト商品');

        $this->actingAs($user)->post(route('csv-imports.confirm'));

        $this->assertDatabaseHas('products', [
            'product_name' => '賞味期限テスト商品',
            'maker_name' => 'メーカー製菓',
        ]);
        $this->assertDatabaseHas('csv_import_batches', ['detected_encoding' => 'SJIS-win']);
    }

    public function test_matching_priority_is_respected_across_a_full_import(): void
    {
        $user = User::factory()->create(['role' => UserRole::Admin]);
        $matchedByCode = Product::factory()->create([
            'company_id' => $user->company_id,
            'internal_product_code' => 'P100',
            'jan_code' => '1111111111111',
            'product_name' => '旧:自社コードで一致',
        ]);
        $matchedByJan = Product::factory()->create([
            'company_id' => $user->company_id,
            'internal_product_code' => null,
            'jan_code' => '2222222222222',
            'product_name' => '旧:JANコードで一致',
        ]);

        $csv = "商品名,店舗コード,店舗名,JANコード,自社商品コード\n".
            // ①自社商品コードが既存と一致 -> そちらを優先して更新（JANコードは異なる値でも無視される）
            "新:自社コードで一致,S001,店舗A,9999999999999,P100\n".
            // ②自社商品コード指定なし、JANコードが既存と一致 -> JANコードで更新
            "新:JANコードで一致,S002,店舗B,2222222222222,\n".
            // ③どちらも未知 -> 新規作成
            "新規商品,S003,店舗C,3333333333333,\n";

        $this->actingAs($user)->post(route('csv-imports.preview'), [
            'file' => $this->csvFile($csv),
            'scope' => 'all_stores',
        ]);
        $this->actingAs($user)->post(route('csv-imports.confirm'));

        $this->assertDatabaseHas('products', [
            'id' => $matchedByCode->id,
            'product_name' => '新:自社コードで一致',
            'jan_code' => '9999999999999',
        ]);
        $this->assertDatabaseHas('products', [
            'id' => $matchedByJan->id,
            'product_name' => '新:JANコードで一致',
        ]);
        $this->assertDatabaseHas('products', [
            'product_name' => '新規商品',
            'jan_code' => '3333333333333',
        ]);
        $this->assertDatabaseCount('products', 3);
    }
}
