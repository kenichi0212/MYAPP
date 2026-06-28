<?php

namespace Tests\Unit;

use App\Enums\UserRole;
use App\Models\User;
use Tests\TestCase;

class UserRoleTest extends TestCase
{
    public function test_store_staff_cannot_manage_all_stores(): void
    {
        $this->assertFalse(UserRole::StoreStaff->canManageAllStores());
    }

    public function test_hq_staff_and_admin_can_manage_all_stores(): void
    {
        $this->assertTrue(UserRole::HqStaff->canManageAllStores());
        $this->assertTrue(UserRole::Admin->canManageAllStores());
    }

    public function test_only_hq_staff_and_admin_can_import_csv(): void
    {
        $this->assertFalse(UserRole::StoreStaff->canImportCsv());
        $this->assertTrue(UserRole::HqStaff->canImportCsv());
        $this->assertTrue(UserRole::Admin->canImportCsv());
    }

    public function test_only_admin_can_manage_users(): void
    {
        $this->assertFalse(UserRole::StoreStaff->canManageUsers());
        $this->assertFalse(UserRole::HqStaff->canManageUsers());
        $this->assertTrue(UserRole::Admin->canManageUsers());
    }

    public function test_user_role_helper_methods(): void
    {
        $admin = User::factory()->make(['role' => UserRole::Admin]);
        $hqStaff = User::factory()->make(['role' => UserRole::HqStaff]);
        $storeStaff = User::factory()->make(['role' => UserRole::StoreStaff]);

        $this->assertTrue($admin->isAdmin());
        $this->assertFalse($admin->isHqStaff());

        $this->assertTrue($hqStaff->isHqStaff());
        $this->assertFalse($hqStaff->isAdmin());

        $this->assertTrue($storeStaff->isStoreStaff());
        $this->assertFalse($storeStaff->isAdmin());
    }
}
