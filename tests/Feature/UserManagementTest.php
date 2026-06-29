<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\Store;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_view_user_list(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);

        $response = $this->actingAs($admin)->get(route('users.index'));

        $response->assertOk();
    }

    public function test_hq_staff_cannot_view_user_list(): void
    {
        $hqStaff = User::factory()->create(['role' => UserRole::HqStaff]);

        $response = $this->actingAs($hqStaff)->get(route('users.index'));

        $response->assertForbidden();
    }

    public function test_store_staff_cannot_view_user_list(): void
    {
        $storeStaff = User::factory()->create(['role' => UserRole::StoreStaff]);

        $response = $this->actingAs($storeStaff)->get(route('users.index'));

        $response->assertForbidden();
    }

    public function test_guest_is_redirected_to_login(): void
    {
        $response = $this->get(route('users.index'));

        $response->assertRedirect('/login');
    }

    public function test_admin_can_create_user(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        $store = Store::factory()->create(['company_id' => $admin->company_id]);

        $response = $this->actingAs($admin)->post(route('users.store'), [
            'name' => '新規担当者',
            'email' => 'new-staff@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
            'role' => UserRole::StoreStaff->value,
            'store_id' => $store->id,
        ]);

        $response->assertRedirect(route('users.index'));
        $this->assertDatabaseHas('users', [
            'email' => 'new-staff@example.com',
            'role' => UserRole::StoreStaff->value,
            'store_id' => $store->id,
            'company_id' => $admin->company_id,
        ]);
    }

    public function test_creating_store_staff_without_store_fails_validation(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);

        $response = $this->actingAs($admin)->post(route('users.store'), [
            'name' => '新規担当者',
            'email' => 'new-staff@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
            'role' => UserRole::StoreStaff->value,
        ]);

        $response->assertSessionHasErrors('store_id');
    }

    public function test_admin_can_update_user(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        $target = User::factory()->create(['role' => UserRole::StoreStaff, 'company_id' => $admin->company_id]);

        $response = $this->actingAs($admin)->put(route('users.update', $target), [
            'name' => '更新後の名前',
            'email' => $target->email,
            'role' => UserRole::HqStaff->value,
            'store_id' => null,
        ]);

        $response->assertRedirect(route('users.index'));
        $this->assertDatabaseHas('users', [
            'id' => $target->id,
            'name' => '更新後の名前',
            'role' => UserRole::HqStaff->value,
        ]);
    }

    public function test_admin_can_toggle_user_active_state(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        $target = User::factory()->create(['role' => UserRole::StoreStaff, 'company_id' => $admin->company_id, 'is_active' => true]);

        $response = $this->actingAs($admin)->patch(route('users.toggle-active', $target));

        $response->assertRedirect(route('users.index'));
        $this->assertDatabaseHas('users', [
            'id' => $target->id,
            'is_active' => false,
        ]);
    }
}
