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
            'priority' => fake()->randomElement(['A', 'B', 'C', 'D']),
            'segment' => fake()->randomElement(['FINEDINING', 'FASTFOOD', 'HEALTHCARE', 'EDUCATION']),
            'type' => fake()->randomElement(['PROSPECT', 'CUSTOMER', 'LEAD']),
            'address' => fake()->streetAddress(),
            'city' => fake()->city(),
            'state' => fake()->stateAbbr(),
            'zipCode' => fake()->postcode(),
            'phone' => fake()->phoneNumber(),
            'email' => fake()->companyEmail(),
            'website' => fake()->domainName(),
            'notes' => fake()->paragraph(),
            'estimatedRevenue' => fake()->randomFloat(2, 10000, 500000),
            'employeeCount' => fake()->numberBetween(5, 500),
            'primaryContact' => fake()->name(),
            'lastContactDate' => fake()->optional()->dateTimeBetween('-30 days', 'now'),
            'nextFollowUpDate' => fake()->optional()->dateTimeBetween('now', '+30 days'),
            'status' => fake()->randomElement(['ACTIVE', 'INACTIVE', 'PENDING']),
        ];
    }
}
