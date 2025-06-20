<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Organization>
 */
class OrganizationFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $segments = ['Fine Dining', 'Healthcare', 'Catering', 'Fast Food', 'Education', 'Corporate'];
        $distributors = ['Sysco', 'USF', 'Direct', 'PFG', 'McLane', 'KeHE'];
        $priorities = ['A', 'B', 'C'];
        $accountManagers = ['Sarah Mitchell', 'Mike Johnson', 'David Chen', 'Lisa Wang', 'Tom Rodriguez'];

        return [
            'name' => fake()->company(),
            'address' => fake()->streetAddress(),
            'city' => fake()->city(),
            'state' => fake()->stateAbbr(),
            'zip_code' => fake()->postcode(),
            'phone' => fake()->phoneNumber(),
            'website' => fake()->domainName(),
            'notes' => fake()->paragraph(),
            'segment' => fake()->randomElement($segments),
            'distributor' => fake()->randomElement($distributors),
            'account_manager' => fake()->randomElement($accountManagers),
            'priority' => fake()->randomElement($priorities),
            'last_contact' => fake()->dateTimeBetween('-30 days', 'now'),
            'is_active' => true,
        ];
    }
}
