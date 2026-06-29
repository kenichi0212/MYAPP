<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BarcodeScanPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get(route('barcode-scan.create'))->assertRedirect(route('login'));
    }

    public function test_authenticated_user_can_view_barcode_scan_page(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('barcode-scan.create'))
            ->assertOk()
            ->assertSee('バーコード読取')
            ->assertSeeHtml('id="scanner-video"');
    }
}
