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
        Schema::create('disposal_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies');
            $table->foreignId('product_id')->constrained('products');
            $table->foreignId('store_id')->constrained('stores');
            $table->date('expiry_date');
            $table->enum('process_type', ['disposal', 'discount', 'return', 'other']);
            $table->unsignedInteger('quantity');
            $table->foreignId('processed_by')->constrained('users');
            $table->timestamp('processed_at');
            $table->text('note')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['company_id', 'product_id', 'store_id', 'expiry_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('disposal_logs');
    }
};
