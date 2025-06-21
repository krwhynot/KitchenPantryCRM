<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Interaction>
 */
class InteractionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $types = ['Phone Call', 'Email', 'In-Person Meeting', 'Site Visit', 'Proposal Sent', 'Follow-up'];
        $outcomes = ['Positive', 'Neutral', 'Negative', 'Pending', 'Closed Won', 'Closed Lost'];
        $interactionDate = fake()->dateTimeBetween('-60 days', 'now');

        return [
            'organization_id' => \App\Models\Organization::factory(),
            'contact_id' => \App\Models\Contact::factory(),
            'user_id' => \App\Models\User::factory(),
            'type' => fake()->randomElement($types),
            'date' => $interactionDate,
            'notes' => fake()->paragraph(),
        ];
    }
}
