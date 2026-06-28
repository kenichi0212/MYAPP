<?php

namespace Tests\Unit;

use App\Enums\UserRole;
use App\Models\Company;
use App\Models\ExpiryCheckLog;
use App\Models\Store;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExpiryCheckLogPolicyTest extends TestCase
{
    use RefreshDatabase;

    public function test_store_staff_can_register_for_their_own_store(): void
    {
        $company = Company::first();
        $store = Store::factory()->create(['company_id' => $company->id]);
        $user = User::factory()->create(['role' => UserRole::StoreStaff, 'store_id' => $store->id]);

        $this->assertTrue($user->can('create', [ExpiryCheckLog::class, $store]));
    }

    public function test_store_staff_cannot_register_for_another_store(): void
    {
        $company = Company::first();
        $ownStore = Store::factory()->create(['company_id' => $company->id]);
        $otherStore = Store::factory()->create(['company_id' => $company->id]);
        $user = User::factory()->create(['role' => UserRole::StoreStaff, 'store_id' => $ownStore->id]);

        $this->assertFalse($user->can('create', [ExpiryCheckLog::class, $otherStore]));
    }

    public function test_hq_staff_can_register_for_any_store(): void
    {
        $company = Company::first();
        $store = Store::factory()->create(['company_id' => $company->id]);
        $user = User::factory()->create(['role' => UserRole::HqStaff, 'store_id' => null]);

        $this->assertTrue($user->can('create', [ExpiryCheckLog::class, $store]));
    }

    public function test_admin_can_register_for_any_store(): void
    {
        $company = Company::first();
        $store = Store::factory()->create(['company_id' => $company->id]);
        $user = User::factory()->create(['role' => UserRole::Admin, 'store_id' => null]);

        $this->assertTrue($user->can('create', [ExpiryCheckLog::class, $store]));
    }
}
