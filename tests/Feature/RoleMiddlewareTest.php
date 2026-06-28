<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class RoleMiddlewareTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Route::middleware(['web', 'auth', 'role:admin,hq_staff'])
            ->get('/_test/admin-or-hq-only', fn () => 'ok');
    }

    public function test_guest_is_redirected_to_login(): void
    {
        $response = $this->get('/_test/admin-or-hq-only');

        $response->assertRedirect('/login');
    }

    public function test_store_staff_is_forbidden(): void
    {
        $user = User::factory()->create(['role' => UserRole::StoreStaff]);

        $response = $this->actingAs($user)->get('/_test/admin-or-hq-only');

        $response->assertForbidden();
    }

    public function test_hq_staff_is_allowed(): void
    {
        $user = User::factory()->create(['role' => UserRole::HqStaff]);

        $response = $this->actingAs($user)->get('/_test/admin-or-hq-only');

        $response->assertOk();
    }

    public function test_admin_is_allowed(): void
    {
        $user = User::factory()->create(['role' => UserRole::Admin]);

        $response = $this->actingAs($user)->get('/_test/admin-or-hq-only');

        $response->assertOk();
    }
}
