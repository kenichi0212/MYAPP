<?php

namespace Database\Factories;

use App\Models\Company;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Product>
 */
class ProductFactory extends Factory
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
            'internal_product_code' => null,
            'jan_code' => $this->faker->unique()->ean13(),
            'product_name' => $this->faker->words(3, true),
            'maker_name' => $this->faker->company(),
            'name_source' => 'master',
        ];
    }
}
