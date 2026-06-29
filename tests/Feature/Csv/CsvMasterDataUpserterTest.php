<?php

namespace Tests\Feature\Csv;

use App\Models\Company;
use App\Models\StaffMember;
use App\Models\Store;
use App\Models\StoreGroup;
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
}
