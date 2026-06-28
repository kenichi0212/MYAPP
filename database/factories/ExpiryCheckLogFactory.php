<?php

namespace Database\Factories;

use App\Models\Company;
use App\Models\Product;
use App\Models\Store;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ExpiryCheckLog>
 */
class ExpiryCheckLogFactory extends Factory
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
            'expiry_date' => $this->faker->dateTimeBetween('now', '+6 months')->format('Y-m-d'),
            'quantity' => $this->faker->numberBetween(1, 100),
            'is_zero_report' => false,
            'data_source' => 'master',
            'checked_by' => User::first()->id,
            'checked_at' => $this->faker->dateTimeBetween('-1 month', 'now'),
            'note' => null,
        ];
    }

    public function zeroReport(): static
    {
        return $this->state(fn () => [
            'quantity' => 0,
            'is_zero_report' => true,
        ]);
    }
}
