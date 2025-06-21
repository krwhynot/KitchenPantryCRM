<?php

namespace Database\Seeders;

use App\Models\SystemSetting;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class CrmPriorityLevelsSeeder extends Seeder
{
    /**
     * Seed CRM priority levels configuration.
     * 
     * Creates A-D priority levels with colors for CRM system.
     * Uses idempotent operations to prevent duplicates.
     */
    public function run(): void
    {
        $this->command->info('📊 Seeding CRM Priority Levels...');
        
        // Priority levels configuration with environment-specific variations
        $priorityLevels = $this->getPriorityLevelsData();
        
        DB::transaction(function () use ($priorityLevels) {
            // Use updateOrCreate for idempotent seeding
            SystemSetting::updateOrCreate(
                [
                    'key' => 'crm.priority_levels',
                    'category' => 'crm'
                ],
                [
                    'value' => json_encode($priorityLevels),
                    'type' => 'json',
                    'description' => 'CRM priority levels (A-D) with associated colors for visual categorization',
                    'default_value' => json_encode($this->getDefaultPriorityLevels()),
                    'validation_rules' => json_encode(['required', 'json']),
                    'ui_component' => 'json_editor',
                    'is_public' => false,
                    'sort_order' => 10,
                ]
            );
        });
        
        $this->command->info('✅ Priority Levels seeded successfully');
    }
    
    /**
     * Get priority levels data based on environment
     */
    private function getPriorityLevelsData(): array
    {
        $basePriorityLevels = $this->getDefaultPriorityLevels();
        
        // Environment-specific customizations
        if (app()->environment('production')) {
            // Production: Use standard business colors
            return $basePriorityLevels;
        } elseif (app()->environment(['local', 'development', 'staging'])) {
            // Development: Add extra context for demos
            return array_map(function ($level) {
                $level['demo_description'] = $this->getDemoDescription($level['label']);
                $level['sample_accounts'] = $this->getSampleAccountsForPriority($level['label']);
                return $level;
            }, $basePriorityLevels);
        } else {
            // Testing: Minimal data for consistency
            return $basePriorityLevels;
        }
    }
    
    /**
     * Get default priority levels configuration
     */
    private function getDefaultPriorityLevels(): array
    {
        return [
            'A' => [
                'label' => 'High Priority',
                'color' => '#dc3545',
                'description' => 'Highest priority accounts requiring immediate attention',
                'weight' => 4,
                'follow_up_days' => 1,
            ],
            'B' => [
                'label' => 'Medium-High Priority',
                'color' => '#fd7e14',
                'description' => 'Important accounts with strong potential',
                'weight' => 3,
                'follow_up_days' => 3,
            ],
            'C' => [
                'label' => 'Medium Priority',
                'color' => '#ffc107',
                'description' => 'Standard priority accounts for regular follow-up',
                'weight' => 2,
                'follow_up_days' => 7,
            ],
            'D' => [
                'label' => 'Low Priority',
                'color' => '#28a745',
                'description' => 'Lower priority accounts for long-term cultivation',
                'weight' => 1,
                'follow_up_days' => 14,
            ],
        ];
    }
    
    /**
     * Get demo description for development environments
     */
    private function getDemoDescription(string $label): string
    {
        return match($label) {
            'High Priority' => 'Large restaurant chains with immediate decision-making authority',
            'Medium-High Priority' => 'Established restaurants with growth potential',
            'Medium Priority' => 'Stable businesses with regular ordering patterns',
            'Low Priority' => 'Small accounts or prospects in early stages',
            default => 'Standard priority account',
        };
    }
    
    /**
     * Get sample account types for demo purposes
     */
    private function getSampleAccountsForPriority(string $label): array
    {
        return match($label) {
            'High Priority' => ['Fine Dining Chains', 'Hotel Restaurant Groups', 'Large Catering Companies'],
            'Medium-High Priority' => ['Established Fine Dining', 'Growing Restaurant Groups', 'High-Volume Cafeterias'],
            'Medium Priority' => ['Casual Dining', 'Local Restaurant Chains', 'Specialty Food Services'],
            'Low Priority' => ['Food Trucks', 'Small Cafes', 'Startup Restaurants'],
            default => ['General Accounts'],
        };
    }
    
    /**
     * Validate priority levels data structure
     */
    private function validatePriorityData(array $data): bool
    {
        $validator = Validator::make(['priority_levels' => $data], [
            'priority_levels' => 'required|array',
            'priority_levels.*.label' => 'required|string|max:255',
            'priority_levels.*.color' => 'required|string|regex:/^#[0-9A-Fa-f]{6}$/',
            'priority_levels.*.description' => 'nullable|string|max:500',
            'priority_levels.*.weight' => 'nullable|integer|min:1|max:10',
            'priority_levels.*.follow_up_days' => 'nullable|integer|min:1|max:365',
        ]);
        
        if ($validator->fails()) {
            $this->command->error('Priority levels validation failed:');
            foreach ($validator->errors()->all() as $error) {
                $this->command->error("  - {$error}");
            }
            return false;
        }
        
        return true;
    }
}