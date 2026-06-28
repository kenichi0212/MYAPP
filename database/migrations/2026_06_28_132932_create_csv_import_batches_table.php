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
        Schema::create('csv_import_batches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained();
            $table->string('file_name');
            $table->enum('scope', ['all_stores', 'store_group']);
            $table->foreignId('store_group_id')->nullable()->constrained();
            $table->foreignId('imported_by')->constrained('users');
            $table->timestamp('imported_at');
            $table->string('detected_encoding', 20)->nullable();
            $table->unsignedInteger('total_rows');
            $table->unsignedInteger('success_count');
            $table->unsignedInteger('error_count');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('csv_import_batches');
    }
};
