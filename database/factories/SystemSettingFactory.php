<?php

namespace Database\Factories;

use App\Models\SystemSetting;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\SystemSetting>
 */
class SystemSettingFactory extends Factory
{
    protected $model = SystemSetting::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $types = array_keys(SystemSetting::ALLOWED_TYPES);
        $categories = array_keys(SystemSetting::ALLOWED_CATEGORIES);
        
        return [
            'key' => fake()->unique()->slug(2),
            'value' => $this->generateValueByType('string'),
            'category' => fake()->randomElement($categories),
            'type' => 'string',
            'description' => fake()->sentence(),
            'is_public' => fake()->boolean(30),
            'sort_order' => fake()->numberBetween(0, 100),
        ];
    }

    // Type-specific factory states
    public function string(): static
    {
        return $this->state(['type' => 'string', 'value' => fake()->sentence()]);
    }

    public function integer(): static
    {
        return $this->state(['type' => 'integer', 'value' => (string) fake()->numberBetween(1, 1000)]);
    }

    public function boolean(): static
    {
        return $this->state(['type' => 'boolean', 'value' => fake()->boolean() ? '1' : '0']);
    }

    public function json(): static
    {
        return $this->state([
            'type' => 'json',
            'value' => json_encode(['key1' => fake()->word(), 'key2' => fake()->numberBetween(1, 100)]),
        ]);
    }

    public function array(): static
    {
        return $this->state(['type' => 'array', 'value' => json_encode(fake()->words(5))]);
    }

    public function color(): static
    {
        return $this->state(['type' => 'color', 'value' => fake()->hexColor()]);
    }

    public function category(string $category): static
    {
        return $this->state(['category' => $category]);
    }

    // Category-specific states for CRM testing
    public function crmSetting(): static
    {
        return $this->state([
            'category' => 'crm',
            'key' => fake()->randomElement(['priority_levels', 'sales_stages', 'contact_roles']),
        ]);
    }

    public function system(): static
    {
        return $this->category('system');
    }

    public function crm(): static
    {
        return $this->category('crm');
    }

    private function generateValueByType(string $type): string
    {
        return match ($type) {
            'integer' => (string) fake()->numberBetween(1, 1000),
            'boolean' => fake()->boolean() ? '1' : '0',
            'json' => json_encode(['sample' => fake()->word()]),
            'array' => json_encode(fake()->words(3)),
            'color' => fake()->hexColor(),
            default => fake()->sentence(),
        };
    }
}
