<?php

namespace Database\Factories;

use App\Models\Company;
use App\Models\Product;
use App\Models\Store;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ProductStoreAssignment>
 */
class ProductStoreAssignmentFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'company_id' => Company::first()->id,
            'product_id' => Product::factory(),
            'store_id' => Store::factory(),
            'staff_master_id' => null,
            'import_batch_id' => null,
            'is_active' => true,
        ];
    }
}
