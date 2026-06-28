<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained();
            $table->string('internal_product_code', 50)->nullable();
            $table->string('jan_code', 13)->nullable();
            $table->string('product_name');
            $table->string('maker_name')->nullable();
            $table->enum('name_source', ['master', 'api', 'manual']);
            $table->timestamps();
        });

        // internal_product_codeがNULLでない場合のみ(company_id, internal_product_code)を一意とする部分インデックス。
        // Laravelのスキーマビルダーは部分インデックスを表現できないため生SQLで作成する。
        DB::statement(
            'CREATE UNIQUE INDEX products_company_id_internal_product_code_unique '.
            'ON products (company_id, internal_product_code) '.
            'WHERE internal_product_code IS NOT NULL'
        );
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
