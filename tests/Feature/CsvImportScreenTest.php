<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\StoreGroup;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CsvImportScreenTest extends TestCase
{
    use RefreshDatabase;

    public function test_hq_staff_can_view_csv_import_form(): void
    {
        $user = User::factory()->create(['role' => UserRole::HqStaff]);

        $response = $this->actingAs($user)->get(route('csv-imports.create'));

        $response->assertOk();
    }

    public function test_admin_can_view_csv_import_form(): void
    {
        $user = User::factory()->create(['role' => UserRole::Admin]);

        $response = $this->actingAs($user)->get(route('csv-imports.create'));

        $response->assertOk();
    }

    public function test_store_staff_cannot_view_csv_import_form(): void
    {
        $user = User::factory()->create(['role' => UserRole::StoreStaff]);

        $response = $this->actingAs($user)->get(route('csv-imports.create'));

        $response->assertForbidden();
    }

    public function test_guest_is_redirected_to_login(): void
    {
        $response = $this->get(route('csv-imports.create'));

        $response->assertRedirect('/login');
    }

    public function test_form_lists_store_groups_for_the_scope_selection(): void
    {
        $user = User::factory()->create(['role' => UserRole::Admin]);
        $group = StoreGroup::factory()->create(['company_id' => $user->company_id, 'group_name' => 'テスト対象グループ']);

        $response = $this->actingAs($user)->get(route('csv-imports.create'));

        $response->assertSee('テスト対象グループ');
    }
}
