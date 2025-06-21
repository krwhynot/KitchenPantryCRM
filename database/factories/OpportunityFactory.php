<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Opportunity>
 */
class OpportunityFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'organization_id' => \App\Models\Organization::factory(),
            'contact_id' => \App\Models\Contact::factory(),
            'user_id' => \App\Models\User::factory(),
            'title' => fake()->sentence(3),
            'description' => fake()->paragraph(),
            'value' => fake()->randomFloat(2, 1000, 100000),
            'stage' => fake()->randomElement(['lead', 'prospect', 'proposal', 'negotiation', 'closed']),
            'status' => fake()->randomElement(['open', 'won', 'lost']),
            'expectedCloseDate' => fake()->dateTimeBetween('now', '+6 months'),
            'isActive' => fake()->boolean(80),
        ];
    }
}
