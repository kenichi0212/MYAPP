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
}
