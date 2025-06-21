<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ProductLine>
 */
class ProductLineFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $foodCategories = [
            'Frozen Foods', 'Dairy Products', 'Fresh Produce', 'Meat & Poultry', 
            'Seafood', 'Bakery Items', 'Beverages', 'Condiments & Sauces',
            'Dry Goods', 'Organic Products', 'Specialty Items', 'Desserts'
        ];

        return [
            'principal_id' => \App\Models\Principal::factory(),
            'name' => fake()->randomElement($foodCategories),
            'description' => fake()->optional()->paragraph(),
            'is_active' => fake()->boolean(90),
        ];
    }
}
