<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('company_id')->default(1)->constrained();
            $table->enum('role', ['store_staff', 'hq_staff', 'admin'])->default('store_staff');
            $table->foreignId('store_id')->nullable()->constrained();
            $table->foreignId('staff_master_id')->nullable()->constrained('staff_master');
            $table->boolean('is_active')->default(true);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('company_id');
            $table->dropColumn('role');
            $table->dropConstrainedForeignId('store_id');
            $table->dropConstrainedForeignId('staff_master_id');
            $table->dropColumn('is_active');
        });
    }
};
