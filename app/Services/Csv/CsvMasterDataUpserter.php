<?php

namespace App\Services\Csv;

use App\Models\StaffMember;
use App\Models\Store;
use App\Models\StoreGroup;

class CsvMasterDataUpserter
{
    /**
     * 店舗グループコードをもとに(company_id, group_code)でアップサートする（SPEC.md 10.）。
     * グループコードが無い行は対象外（null）とする。
     */
    public function upsertStoreGroup(int $companyId, ?string $groupCode, ?string $groupName): ?StoreGroup
    {
        if (empty($groupCode)) {
            return null;
        }

        $group = StoreGroup::firstOrNew([
            'company_id' => $companyId,
            'group_code' => $groupCode,
        ]);

        $group->group_name = $groupName;
        $group->save();

        return $group;
    }

    /**
     * 店舗コードをもとに(company_id, store_code)でアップサートする（SPEC.md 10.）。
     */
    public function upsertStore(int $companyId, string $storeCode, ?string $storeName, ?string $officeName, ?int $storeGroupId): Store
    {
        $store = Store::firstOrNew([
            'company_id' => $companyId,
            'store_code' => $storeCode,
        ]);

        $store->store_name = $storeName;
        $store->office_name = $officeName;
        $store->store_group_id = $storeGroupId;
        $store->save();

        return $store;
    }

    /**
     * 担当者名をもとに(company_id, staff_name)でアップサートする（SPEC.md 10.）。
     * 担当者名が無い行は対象外（null）とする。
     */
    public function upsertStaffMember(int $companyId, ?string $staffName): ?StaffMember
    {
        if (empty($staffName)) {
            return null;
        }

        return StaffMember::firstOrCreate([
            'company_id' => $companyId,
            'staff_name' => $staffName,
        ]);
    }
}
