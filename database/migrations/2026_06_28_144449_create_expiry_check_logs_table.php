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
        Schema::create('expiry_check_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained();
            $table->foreignId('product_id')->constrained();
            $table->foreignId('store_id')->constrained();
            $table->date('expiry_date');
            $table->unsignedInteger('quantity');
            $table->boolean('is_zero_report');
            $table->enum('data_source', ['master', 'api', 'manual']);
            $table->foreignId('checked_by')->constrained('users');
            $table->timestamp('checked_at');
            $table->text('note')->nullable();

            $table->index(['company_id', 'store_id', 'expiry_date']);
            $table->index(
                ['company_id', 'product_id', 'store_id', 'expiry_date', 'checked_at'],
                'expiry_check_logs_lot_latest_index'
            );
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('expiry_check_logs');
    }
};
