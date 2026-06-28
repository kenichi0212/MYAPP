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
        Schema::create('csv_import_errors', function (Blueprint $table) {
            $table->id();
            $table->foreignId('import_batch_id')->constrained('csv_import_batches');
            $table->unsignedInteger('row_number');
            $table->string('error_reason');
            $table->text('raw_line')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('csv_import_errors');
    }
};
