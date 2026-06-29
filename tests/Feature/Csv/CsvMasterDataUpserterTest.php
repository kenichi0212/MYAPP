<?php

namespace Tests\Feature\Csv;

use App\Models\Company;
use App\Models\CsvImportBatch;
use App\Models\Product;
use App\Models\ProductStoreAssignment;
use App\Models\StaffMember;
use App\Models\Store;
use App\Models\StoreGroup;
use App\Models\User;
use App\Services\Csv\CsvMasterDataUpserter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CsvMasterDataUpserterTest extends TestCase
{
    use RefreshDatabase;

    public function test_upsert_store_group_creates_new_group(): void
    {
        $company = Company::first();
        $upserter = new CsvMasterDataUpserter();

        $group = $upserter->upsertStoreGroup($company->id, 'G001', '関東チェーン');

        $this->assertInstanceOf(StoreGroup::class, $group);
        $this->assertDatabaseHas('store_groups', [
            'company_id' => $company->id,
            'group_code' => 'G001',
            'group_name' => '関東チェーン',
        ]);
    }

    public function test_upsert_store_group_updates_existing_group_name(): void
    {
        $company = Company::first();
        $existing = StoreGroup::factory()->create([
            'company_id' => $company->id,
            'group_code' => 'G001',
            'group_name' => '旧名称',
        ]);
        $upserter = new CsvMasterDataUpserter();

        $group = $upserter->upsertStoreGroup($company->id, 'G001', '新名称');

        $this->assertSame($existing->id, $group->id);
        $this->assertDatabaseHas('store_groups', [
            'id' => $existing->id,
            'group_name' => '新名称',
        ]);
        $this->assertDatabaseCount('store_groups', 1);
    }

    public function test_upsert_store_group_returns_null_when_code_is_empty(): void
    {
        $company = Company::first();
        $upserter = new CsvMasterDataUpserter();

        $this->assertNull($upserter->upsertStoreGroup($company->id, null, null));
        $this->assertDatabaseCount('store_groups', 0);
    }

    public function test_upsert_store_creates_new_store(): void
    {
        $company = Company::first();
        $upserter = new CsvMasterDataUpserter();

        $store = $upserter->upsertStore($company->id, 'S001', 'テスト店舗', '事業所A', null);

        $this->assertInstanceOf(Store::class, $store);
        $this->assertDatabaseHas('stores', [
            'company_id' => $company->id,
            'store_code' => 'S001',
            'store_name' => 'テスト店舗',
            'office_name' => '事業所A',
        ]);
    }

    public function test_upsert_store_updates_existing_store(): void
    {
        $company = Company::first();
        $existing = Store::factory()->create([
            'company_id' => $company->id,
            'store_code' => 'S001',
            'store_name' => '旧店舗名',
        ]);
        $group = StoreGroup::factory()->create(['company_id' => $company->id]);
        $upserter = new CsvMasterDataUpserter();

        $store = $upserter->upsertStore($company->id, 'S001', '新店舗名', '事業所B', $group->id);

        $this->assertSame($existing->id, $store->id);
        $this->assertDatabaseHas('stores', [
            'id' => $existing->id,
            'store_name' => '新店舗名',
            'office_name' => '事業所B',
            'store_group_id' => $group->id,
        ]);
        $this->assertDatabaseCount('stores', 1);
    }

    public function test_upsert_staff_member_creates_new_member(): void
    {
        $company = Company::first();
        $upserter = new CsvMasterDataUpserter();

        $staff = $upserter->upsertStaffMember($company->id, '山田太郎');

        $this->assertInstanceOf(StaffMember::class, $staff);
        $this->assertDatabaseHas('staff_master', [
            'company_id' => $company->id,
            'staff_name' => '山田太郎',
        ]);
    }

    public function test_upsert_staff_member_returns_existing_member(): void
    {
        $company = Company::first();
        $existing = StaffMember::factory()->create([
            'company_id' => $company->id,
            'staff_name' => '山田太郎',
        ]);
        $upserter = new CsvMasterDataUpserter();

        $staff = $upserter->upsertStaffMember($company->id, '山田太郎');

        $this->assertSame($existing->id, $staff->id);
        $this->assertDatabaseCount('staff_master', 1);
    }

    public function test_upsert_staff_member_returns_null_when_name_is_empty(): void
    {
        $company = Company::first();
        $upserter = new CsvMasterDataUpserter();

        $this->assertNull($upserter->upsertStaffMember($company->id, null));
        $this->assertDatabaseCount('staff_master', 0);
    }

    public function test_upsert_product_store_assignment_creates_new_assignment(): void
    {
        $company = Company::first();
        $product = Product::factory()->create(['company_id' => $company->id]);
        $store = Store::factory()->create(['company_id' => $company->id]);
        $staff = StaffMember::factory()->create(['company_id' => $company->id]);
        $batch = $this->makeImportBatch($company->id);
        $upserter = new CsvMasterDataUpserter();

        $assignment = $upserter->upsertProductStoreAssignment(
            $company->id,
            $product->id,
            $store->id,
            $staff->id,
            $batch->id,
        );

        $this->assertInstanceOf(ProductStoreAssignment::class, $assignment);
        $this->assertDatabaseHas('product_store_assignments', [
            'company_id' => $company->id,
            'product_id' => $product->id,
            'store_id' => $store->id,
            'staff_master_id' => $staff->id,
            'import_batch_id' => $batch->id,
            'is_active' => true,
        ]);
    }

    public function test_upsert_product_store_assignment_updates_existing_assignment(): void
    {
        $company = Company::first();
        $product = Product::factory()->create(['company_id' => $company->id]);
        $store = Store::factory()->create(['company_id' => $company->id]);
        $existing = ProductStoreAssignment::factory()->create([
            'company_id' => $company->id,
            'product_id' => $product->id,
            'store_id' => $store->id,
            'staff_master_id' => null,
            'import_batch_id' => null,
        ]);
        $newStaff = StaffMember::factory()->create(['company_id' => $company->id]);
        $batch = $this->makeImportBatch($company->id);
        $upserter = new CsvMasterDataUpserter();

        $assignment = $upserter->upsertProductStoreAssignment(
            $company->id,
            $product->id,
            $store->id,
            $newStaff->id,
            $batch->id,
        );

        $this->assertSame($existing->id, $assignment->id);
        $this->assertDatabaseHas('product_store_assignments', [
            'id' => $existing->id,
            'staff_master_id' => $newStaff->id,
            'import_batch_id' => $batch->id,
        ]);
        $this->assertDatabaseCount('product_store_assignments', 1);
    }

    private function makeImportBatch(int $companyId): CsvImportBatch
    {
        return CsvImportBatch::create([
            'company_id' => $companyId,
            'file_name' => 'test.csv',
            'scope' => 'all_stores',
            'imported_by' => User::first()->id,
            'imported_at' => now(),
            'total_rows' => 0,
            'success_count' => 0,
            'error_count' => 0,
        ]);
    }
}
