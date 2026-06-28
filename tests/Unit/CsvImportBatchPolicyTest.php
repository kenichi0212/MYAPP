<?php

namespace Tests\Unit;

use App\Enums\UserRole;
use App\Models\CsvImportBatch;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CsvImportBatchPolicyTest extends TestCase
{
    use RefreshDatabase;

    public function test_store_staff_cannot_import_csv(): void
    {
        $user = User::factory()->create(['role' => UserRole::StoreStaff]);

        $this->assertFalse($user->can('create', CsvImportBatch::class));
    }

    public function test_hq_staff_can_import_csv(): void
    {
        $user = User::factory()->create(['role' => UserRole::HqStaff]);

        $this->assertTrue($user->can('create', CsvImportBatch::class));
    }

    public function test_admin_can_import_csv(): void
    {
        $user = User::factory()->create(['role' => UserRole::Admin]);

        $this->assertTrue($user->can('create', CsvImportBatch::class));
    }
}
