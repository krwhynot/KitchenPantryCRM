<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\SystemSetting>
 */
class SystemSettingFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $settingKeys = [
            'app_name', 'default_currency', 'timezone', 'date_format',
            'email_from_address', 'email_from_name', 'pagination_limit',
            'maintenance_mode', 'debug_enabled', 'cache_enabled'
        ];

        return [
            'key' => fake()->unique()->randomElement($settingKeys),
            'value' => fake()->randomElement([
                fake()->word(),
                fake()->boolean() ? 'true' : 'false',
                fake()->numberBetween(1, 100),
                fake()->email(),
            ]),
        ];
    }
}
