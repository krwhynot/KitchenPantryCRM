<?php

namespace Database\Seeders;

use App\Models\SystemSetting;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class CrmSalesStagesSeeder extends Seeder
{
    /**
     * Seed CRM sales stages configuration.
     * 
     * Creates sales pipeline stages with proper ordering for CRM system.
     * Uses idempotent operations to prevent duplicates.
     */
    public function run(): void
    {
        $this->command->info('🚀 Seeding CRM Sales Stages...');
        
        // Sales stages configuration with environment-specific variations
        $salesStages = $this->getSalesStagesData();
        
        // Validate data before seeding
        if (!$this->validateSalesStagesData($salesStages)) {
            $this->command->error('❌ Sales stages data validation failed');
            return;
        }
        
        DB::transaction(function () use ($salesStages) {
            // Use updateOrCreate for idempotent seeding
            SystemSetting::updateOrCreate(
                [
                    'key' => 'crm.sales_stages',
                    'category' => 'crm'
                ],
                [
                    'value' => json_encode($salesStages),
                    'type' => 'json',
                    'description' => 'CRM sales pipeline stages with ordering and configuration',
                    'default_value' => json_encode($this->getDefaultSalesStages()),
                    'validation_rules' => json_encode(['required', 'json']),
                    'ui_component' => 'json_editor',
                    'is_public' => false,
                    'sort_order' => 20,
                ]
            );
        });
        
        $this->command->info('✅ Sales Stages seeded successfully');
    }
    
    /**
     * Get sales stages data based on environment
     */
    private function getSalesStagesData(): array
    {
        $baseSalesStages = $this->getDefaultSalesStages();
        
        // Environment-specific customizations
        if (app()->environment('production')) {
            // Production: Standard sales stages
            return $baseSalesStages;
        } elseif (app()->environment(['local', 'development', 'staging'])) {
            // Development: Add demo context and examples
            return array_map(function ($stage) {
                $stage['demo_description'] = $this->getDemoDescription($stage['label']);
                $stage['typical_duration_days'] = $this->getTypicalDuration($stage['label']);
                $stage['success_rate_percent'] = $this->getSuccessRate($stage['label']);
                return $stage;
            }, $baseSalesStages);
        } else {
            // Testing: Minimal data for consistency
            return $baseSalesStages;
        }
    }
    
    /**
     * Get default sales stages configuration
     */
    private function getDefaultSalesStages(): array
    {
        return [
            'lead' => [
                'label' => 'Lead',
                'description' => 'Initial contact or inquiry from potential customer',
                'order' => 1,
                'is_active' => true,
                'color' => '#6c757d',
                'icon' => 'heroicon-o-user-plus',
            ],
            'qualified' => [
                'label' => 'Qualified',
                'description' => 'Lead has been qualified as a potential customer',
                'order' => 2,
                'is_active' => true,
                'color' => '#0d6efd',
                'icon' => 'heroicon-o-check-circle',
            ],
            'proposal' => [
                'label' => 'Proposal Sent',
                'description' => 'Proposal or quote has been sent to customer',
                'order' => 3,
                'is_active' => true,
                'color' => '#fd7e14',
                'icon' => 'heroicon-o-document-text',
            ],
            'negotiation' => [
                'label' => 'Negotiation',
                'description' => 'Active negotiation on terms, pricing, or contract details',
                'order' => 4,
                'is_active' => true,
                'color' => '#ffc107',
                'icon' => 'heroicon-o-chat-bubble-left-right',
            ],
            'closed_won' => [
                'label' => 'Closed Won',
                'description' => 'Deal successfully closed and customer acquired',
                'order' => 5,
                'is_active' => true,
                'is_closed' => true,
                'is_won' => true,
                'color' => '#198754',
                'icon' => 'heroicon-o-trophy',
            ],
            'closed_lost' => [
                'label' => 'Closed Lost',
                'description' => 'Deal lost to competitor or customer decided not to proceed',
                'order' => 6,
                'is_active' => true,
                'is_closed' => true,
                'is_won' => false,
                'color' => '#dc3545',
                'icon' => 'heroicon-o-x-circle',
            ],
        ];
    }
    
    /**
     * Get demo description for development environments
     */
    private function getDemoDescription(string $label): string
    {
        return match($label) {
            'Lead' => 'Restaurant owner filled out contact form or called for information',
            'Qualified' => 'Confirmed budget, decision-making authority, and timeline',
            'Proposal Sent' => 'Detailed product catalog and pricing sent to decision maker',
            'Negotiation' => 'Discussing volume discounts, delivery terms, and contract length',
            'Closed Won' => 'Contract signed, first order placed, account setup complete',
            'Closed Lost' => 'Customer chose competitor or decided to postpone purchase',
            default => 'Standard sales stage',
        };
    }
    
    /**
     * Get typical duration for demo purposes
     */
    private function getTypicalDuration(string $label): int
    {
        return match($label) {
            'Lead' => 7,           // 1 week to qualify
            'Qualified' => 14,     // 2 weeks to prepare proposal
            'Proposal Sent' => 10, // 10 days for customer review
            'Negotiation' => 21,   // 3 weeks for negotiations
            'Closed Won' => 0,     // Final stage
            'Closed Lost' => 0,    // Final stage
            default => 7,
        };
    }
    
    /**
     * Get success rate for demo purposes
     */
    private function getSuccessRate(string $label): int
    {
        return match($label) {
            'Lead' => 60,          // 60% of leads get qualified
            'Qualified' => 80,     // 80% of qualified leads get proposals
            'Proposal Sent' => 45, // 45% of proposals move to negotiation
            'Negotiation' => 70,   // 70% of negotiations close (won or lost)
            'Closed Won' => 100,   // Final success stage
            'Closed Lost' => 0,    // Final failure stage
            default => 50,
        };
    }
    
    /**
     * Validate sales stages data structure
     */
    private function validateSalesStagesData(array $data): bool
    {
        $validator = Validator::make(['sales_stages' => $data], [
            'sales_stages' => 'required|array',
            'sales_stages.*.label' => 'required|string|max:255',
            'sales_stages.*.description' => 'nullable|string|max:500',
            'sales_stages.*.order' => 'required|integer|min:1|max:50',
            'sales_stages.*.is_active' => 'required|boolean',
            'sales_stages.*.color' => 'nullable|string|regex:/^#[0-9A-Fa-f]{6}$/',
            'sales_stages.*.icon' => 'nullable|string|max:100',
            'sales_stages.*.is_closed' => 'nullable|boolean',
            'sales_stages.*.is_won' => 'nullable|boolean',
        ]);
        
        if ($validator->fails()) {
            $this->command->error('Sales stages validation failed:');
            foreach ($validator->errors()->all() as $error) {
                $this->command->error("  - {$error}");
            }
            return false;
        }
        
        // Additional business logic validation
        $orders = array_column($data, 'order');
        if (count($orders) !== count(array_unique($orders))) {
            $this->command->error('Sales stages must have unique order numbers');
            return false;
        }
        
        return true;
    }
}