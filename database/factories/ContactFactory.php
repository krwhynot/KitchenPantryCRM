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
            'first_name' => $firstName,
            'last_name' => $lastName,
            'name' => $firstName . ' ' . $lastName,
            'email' => fake()->unique()->safeEmail(),
            'phone' => fake()->phoneNumber(),
            'title' => fake()->randomElement($positions),
            'position' => fake()->randomElement($positions),
            'priority' => fake()->randomElement($priorities),
            'last_contact' => fake()->dateTimeBetween('-30 days', 'now'),
        ];
    }
}
