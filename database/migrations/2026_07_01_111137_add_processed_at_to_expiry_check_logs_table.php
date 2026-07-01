<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('expiry_check_logs', function (Blueprint $table) {
            $table->timestamp('processed_at')->nullable()->after('checked_at');
            $table->foreignId('processed_by')->nullable()->constrained('users')->after('processed_at');
        });
    }

    public function down(): void
    {
        Schema::table('expiry_check_logs', function (Blueprint $table) {
            $table->dropConstrainedForeignId('processed_by');
            $table->dropColumn('processed_at');
        });
    }
};
