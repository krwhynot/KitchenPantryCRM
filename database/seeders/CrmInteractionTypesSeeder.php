<?php

namespace Database\Seeders;

use App\Models\SystemSetting;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class CrmInteractionTypesSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed CRM interaction types configuration.
     * 
     * Creates interaction types for CRM communication logging.
     * Uses idempotent operations to prevent duplicates.
     */
    public function run(): void
    {
        $this->command->info('📞 Seeding CRM Interaction Types...');
        
        // Track timing for performance monitoring
        $startTime = microtime(true);
        
        // Interaction types configuration with environment-specific variations
        $interactionTypes = $this->getInteractionTypesData();
        
        // Validate data before seeding
        if (!$this->validateInteractionTypesData($interactionTypes)) {
            $this->command->error('❌ Interaction types data validation failed');
            return;
        }
        
        DB::transaction(function () use ($interactionTypes) {
            // Use updateOrCreate for idempotent seeding
            SystemSetting::updateOrCreate(
                [
                    'key' => 'crm.interaction_types',
                    'category' => 'crm'
                ],
                [
                    'value' => json_encode($interactionTypes),
                    'type' => 'json',
                    'description' => 'Available interaction types for CRM communication logging',
                    'default_value' => json_encode($this->getDefaultInteractionTypes()),
                    'validation_rules' => json_encode(['required', 'json']),
                    'ui_component' => 'json_editor',
                    'is_public' => false,
                    'sort_order' => 40,
                ]
            );
        });
        
        $endTime = microtime(true);
        $duration = round($endTime - $startTime, 2);
        
        $this->command->info("✅ Interaction Types seeded successfully in {$duration} seconds");
    }
    
    /**
     * Get interaction types data based on environment
     */
    private function getInteractionTypesData(): array
    {
        $baseInteractionTypes = $this->getDefaultInteractionTypes();
        
        // Environment-specific customizations
        if (app()->environment('production')) {
            // Production: Use standard interaction types
            return $baseInteractionTypes;
        } elseif (app()->environment(['local', 'development', 'staging'])) {
            // Development: Add demo context and examples
            return array_map(function ($type, $key) {
                return [
                    'label' => $type,
                    'demo_description' => $this->getDemoDescription($key),
                    'typical_duration_minutes' => $this->getTypicalDuration($key),
                    'follow_up_required' => $this->getFollowUpRequired($key),
                    'icon' => $this->getIcon($key),
                ];
            }, $baseInteractionTypes, array_keys($baseInteractionTypes));
        } else {
            // Testing: Minimal data for consistency
            return $baseInteractionTypes;
        }
    }
    
    /**
     * Get default interaction types configuration
     */
    private function getDefaultInteractionTypes(): array
    {
        return [
            'call' => 'Phone Call',
            'email' => 'Email',
            'meeting' => 'In-Person Meeting',
            'demo' => 'Product Demo',
            'follow_up' => 'Follow Up',
            'proposal' => 'Proposal Presentation',
            'contract' => 'Contract Discussion',
            'trade_show' => 'Trade Show Meeting',
            'site_visit' => 'Site Visit',
            'training' => 'Product Training',
            'other' => 'Other',
        ];
    }
    
    /**
     * Get demo description for development environments
     */
    private function getDemoDescription(string $key): string
    {
        return match($key) {
            'call' => 'Phone conversation with restaurant manager about product needs',
            'email' => 'Email communication sharing catalogs, pricing, or follow-up information',
            'meeting' => 'Face-to-face meeting at restaurant or customer location',
            'demo' => 'Product demonstration showing preparation techniques and quality',
            'follow_up' => 'Scheduled follow-up after initial contact or proposal',
            'proposal' => 'Formal presentation of pricing and product recommendations',
            'contract' => 'Discussion of contract terms, delivery schedules, and pricing',
            'trade_show' => 'Meeting at food service trade show or industry event',
            'site_visit' => 'Visit to customer location to assess needs and operations',
            'training' => 'Staff training on product usage and preparation techniques',
            'other' => 'Any other type of customer interaction',
            default => 'Standard customer interaction',
        };
    }
    
    /**
     * Get typical duration for demo purposes
     */
    private function getTypicalDuration(string $key): int
    {
        return match($key) {
            'call' => 15,           // 15 minutes phone call
            'email' => 5,           // 5 minutes to send/respond
            'meeting' => 60,        // 1 hour meeting
            'demo' => 45,           // 45 minutes product demo
            'follow_up' => 20,      // 20 minutes follow-up call
            'proposal' => 90,       // 1.5 hours proposal presentation
            'contract' => 120,      // 2 hours contract discussion
            'trade_show' => 30,     // 30 minutes trade show meeting
            'site_visit' => 180,    // 3 hours site visit
            'training' => 240,      // 4 hours training session
            'other' => 30,          // 30 minutes default
            default => 30,
        };
    }
    
    /**
     * Get follow-up requirement for demo purposes
     */
    private function getFollowUpRequired(string $key): bool
    {
        return match($key) {
            'call' => true,         // Usually requires follow-up
            'email' => false,       // May not need immediate follow-up
            'meeting' => true,      // Usually requires follow-up
            'demo' => true,         // Always requires follow-up
            'follow_up' => false,   // This IS the follow-up
            'proposal' => true,     // Requires follow-up on decision
            'contract' => false,    // Final discussion
            'trade_show' => true,   // Requires follow-up
            'site_visit' => true,   // Requires follow-up proposal
            'training' => false,    // Service delivery, no follow-up needed
            'other' => true,        // Generally requires follow-up
            default => true,
        };
    }
    
    /**
     * Get icon for demo purposes
     */
    private function getIcon(string $key): string
    {
        return match($key) {
            'call' => 'heroicon-o-phone',
            'email' => 'heroicon-o-envelope',
            'meeting' => 'heroicon-o-users',
            'demo' => 'heroicon-o-presentation-chart-line',
            'follow_up' => 'heroicon-o-arrow-path',
            'proposal' => 'heroicon-o-document-text',
            'contract' => 'heroicon-o-document-check',
            'trade_show' => 'heroicon-o-building-storefront',
            'site_visit' => 'heroicon-o-map-pin',
            'training' => 'heroicon-o-academic-cap',
            'other' => 'heroicon-o-ellipsis-horizontal',
            default => 'heroicon-o-chat-bubble-left-right',
        };
    }
    
    /**
     * Validate interaction types data structure
     */
    private function validateInteractionTypesData(array $data): bool
    {
        // For development environment with enhanced data
        if (app()->environment(['local', 'development', 'staging'])) {
            $validator = Validator::make(['interaction_types' => $data], [
                'interaction_types' => 'required|array',
                'interaction_types.*.label' => 'required|string|max:255',
                'interaction_types.*.demo_description' => 'string',
                'interaction_types.*.typical_duration_minutes' => 'integer',
                'interaction_types.*.follow_up_required' => 'boolean',
                'interaction_types.*.icon' => 'string',
            ]);
        } else {
            // For production environment with simple strings
            $validator = Validator::make(['interaction_types' => $data], [
                'interaction_types' => 'required|array',
                'interaction_types.*' => 'required|string|max:255',
            ]);
        }
        
        if ($validator->fails()) {
            $this->command->error('Interaction types validation failed:');
            foreach ($validator->errors()->all() as $error) {
                $this->command->error("  - {$error}");
            }
            return false;
        }
        
        return true;
    }
}