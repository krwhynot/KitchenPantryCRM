<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Contact>
 */
class ContactFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $positions = ['General Manager', 'Chef', 'Kitchen Manager', 'Purchasing Manager', 'Operations Director', 'Owner'];
        $priorities = ['A', 'B', 'C'];
        $firstName = fake()->firstName();
        $lastName = fake()->lastName();

        return [
            'organization_id' => \App\Models\Organization::factory(),
            'firstName' => $firstName,
            'lastName' => $lastName,
            'email' => fake()->unique()->safeEmail(),
            'phone' => fake()->phoneNumber(),
            'position' => fake()->randomElement($positions),
            'isPrimary' => fake()->boolean(20), // 20% chance of being primary
            'notes' => fake()->optional()->paragraph(),
        ];
    }
}
