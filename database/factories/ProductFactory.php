<?php

namespace Database\Factories;

use App\Data\Products\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;

class ProductFactory extends Factory
{
    protected $model = Product::class;

    public function definition(): array
    {
        return [
            'sku'         => strtoupper($this->faker->unique()->bothify('SKU-####')),
            'name'        => $this->faker->words(3, true),
            'description' => $this->faker->sentence(),
            'price'       => $this->faker->randomFloat(2, 10, 999),
            'stock'       => $this->faker->numberBetween(0, 500),
        ];
    }
}
