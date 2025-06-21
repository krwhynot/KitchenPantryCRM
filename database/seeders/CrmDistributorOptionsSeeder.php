<?php

namespace Database\Seeders;

use App\Models\SystemSetting;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class CrmDistributorOptionsSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed CRM distributor options configuration.
     * 
     * Creates distributor types for CRM supply chain management.
     * Uses idempotent operations to prevent duplicates.
     */
    public function run(): void
    {
        $this->command->info('🚚 Seeding CRM Distributor Options...');
        
        // Track timing for performance monitoring
        $startTime = microtime(true);
        
        // Distributor options configuration with environment-specific variations
        $distributorOptions = $this->getDistributorOptionsData();
        
        // Validate data before seeding
        if (!$this->validateDistributorOptionsData($distributorOptions)) {
            $this->command->error('❌ Distributor options data validation failed');
            return;
        }
        
        DB::transaction(function () use ($distributorOptions) {
            // Use updateOrCreate for idempotent seeding
            SystemSetting::updateOrCreate(
                [
                    'key' => 'crm.distributor_options',
                    'category' => 'crm'
                ],
                [
                    'value' => json_encode($distributorOptions),
                    'type' => 'json',
                    'description' => 'Available distributor options for CRM supply chain management',
                    'default_value' => json_encode($this->getDefaultDistributorOptions()),
                    'validation_rules' => json_encode(['required', 'json']),
                    'ui_component' => 'json_editor',
                    'is_public' => false,
                    'sort_order' => 60,
                ]
            );
        });
        
        $endTime = microtime(true);
        $duration = round($endTime - $startTime, 2);
        
        $this->command->info("✅ Distributor Options seeded successfully in {$duration} seconds");
    }
    
    /**
     * Get distributor options data based on environment
     */
    private function getDistributorOptionsData(): array
    {
        $baseDistributorOptions = $this->getDefaultDistributorOptions();
        
        // Environment-specific customizations
        if (app()->environment('production')) {
            // Production: Use standard distributor options
            return $baseDistributorOptions;
        } elseif (app()->environment(['local', 'development', 'staging'])) {
            // Development: Add demo context and examples
            return array_map(function ($option, $key) {
                return [
                    'label' => $option,
                    'demo_description' => $this->getDemoDescription($key),
                    'typical_delivery_frequency' => $this->getTypicalDeliveryFrequency($key),
                    'volume_capacity' => $this->getVolumeCapacity($key),
                    'geographic_coverage' => $this->getGeographicCoverage($key),
                    'pricing_model' => $this->getPricingModel($key),
                    'product_range' => $this->getProductRange($key),
                ];
            }, $baseDistributorOptions, array_keys($baseDistributorOptions));
        } else {
            // Testing: Minimal data for consistency
            return $baseDistributorOptions;
        }
    }
    
    /**
     * Get default distributor options configuration
     */
    private function getDefaultDistributorOptions(): array
    {
        return [
            'broadline' => 'Broadline Distributor',
            'specialty' => 'Specialty Distributor',
            'direct' => 'Direct from Manufacturer',
            'local' => 'Local Distributor',
            'regional' => 'Regional Distributor',
            'national' => 'National Chain',
            'cash_carry' => 'Cash & Carry',
            'online_platform' => 'Online Platform',
            'cooperative' => 'Buying Cooperative',
            'commissary' => 'Commissary Service',
            'other' => 'Other',
        ];
    }
    
    /**
     * Get demo description for development environments
     */
    private function getDemoDescription(string $key): string
    {
        return match($key) {
            'broadline' => 'Full-service distributors offering comprehensive product lines including fresh, frozen, dry, and non-food items',
            'specialty' => 'Specialized distributors focusing on specific categories like organic, ethnic, or gourmet products',
            'direct' => 'Direct purchasing from manufacturers, bypassing traditional distribution channels',
            'local' => 'Local or city-based distributors serving specific geographic areas with personalized service',
            'regional' => 'Multi-state regional distributors offering broader coverage than local but more focused than national',
            'national' => 'Large national distribution companies with coast-to-coast coverage and extensive product lines',
            'cash_carry' => 'Self-service wholesale warehouses where customers pick up products directly',
            'online_platform' => 'Digital marketplaces and e-commerce platforms for food service procurement',
            'cooperative' => 'Member-owned purchasing cooperatives providing group buying power and shared resources',
            'commissary' => 'Central kitchen facilities providing prepared foods and ingredients to multiple locations',
            'other' => 'Any other type of distribution or supply chain arrangement',
            default => 'Standard distribution channel',
        };
    }
    
    /**
     * Get typical delivery frequency for demo purposes
     */
    private function getTypicalDeliveryFrequency(string $key): string
    {
        return match($key) {
            'broadline' => '2-3 times per week',      // Full service, regular deliveries
            'specialty' => '1-2 times per week',      // Specialty items, less frequent
            'direct' => 'Weekly to monthly',          // Larger orders, less frequent
            'local' => '3-5 times per week',          // Local service, more flexible
            'regional' => '2-3 times per week',       // Regional efficiency
            'national' => '1-2 times per week',       // Consolidated delivery
            'cash_carry' => 'Customer pickup',        // Customer driven
            'online_platform' => '1-2 times per week', // Platform dependent
            'cooperative' => 'Weekly',                // Coordinated orders
            'commissary' => 'Daily',                  // Fresh prepared foods
            'other' => 'Variable',                    // Depends on arrangement
            default => 'Variable',
        };
    }
    
    /**
     * Get volume capacity for demo purposes
     */
    private function getVolumeCapacity(string $key): string
    {
        return match($key) {
            'broadline' => 'Very High',               // Large distribution networks
            'specialty' => 'Medium',                  // Focused but limited volume
            'direct' => 'High',                      // Manufacturer capacity
            'local' => 'Medium',                     // Limited by local market
            'regional' => 'High',                    // Multi-state coverage
            'national' => 'Very High',               // Nationwide capacity
            'cash_carry' => 'High',                  // Warehouse model
            'online_platform' => 'Variable',         // Depends on suppliers
            'cooperative' => 'High',                 // Group buying power
            'commissary' => 'Medium',                // Kitchen production limits
            'other' => 'Variable',                   // Depends on arrangement
            default => 'Medium',
        };
    }
    
    /**
     * Get geographic coverage for demo purposes
     */
    private function getGeographicCoverage(string $key): string
    {
        return match($key) {
            'broadline' => 'Multi-state regional',
            'specialty' => 'Regional to national',
            'direct' => 'National',
            'local' => 'City or county',
            'regional' => 'Multi-state',
            'national' => 'Coast to coast',
            'cash_carry' => 'Local to regional',
            'online_platform' => 'National',
            'cooperative' => 'Regional',
            'commissary' => 'Local',
            'other' => 'Variable',
            default => 'Regional',
        };
    }
    
    /**
     * Get pricing model for demo purposes
     */
    private function getPricingModel(string $key): string
    {
        return match($key) {
            'broadline' => 'Cost plus markup',
            'specialty' => 'Premium pricing',
            'direct' => 'Manufacturer pricing',
            'local' => 'Competitive local rates',
            'regional' => 'Volume-based pricing',
            'national' => 'Contract pricing',
            'cash_carry' => 'Cash discount pricing',
            'online_platform' => 'Market-based pricing',
            'cooperative' => 'Group buying discounts',
            'commissary' => 'Service-based pricing',
            'other' => 'Variable',
            default => 'Standard markup',
        };
    }
    
    /**
     * Get product range for demo purposes
     */
    private function getProductRange(string $key): string
    {
        return match($key) {
            'broadline' => 'Full line: fresh, frozen, dry, non-food',
            'specialty' => 'Specialized categories only',
            'direct' => 'Manufacturer\'s product line',
            'local' => 'Regional favorites and staples',
            'regional' => 'Broad selection with regional focus',
            'national' => 'Comprehensive national brands',
            'cash_carry' => 'High-volume commodity items',
            'online_platform' => 'Marketplace variety',
            'cooperative' => 'Member-selected products',
            'commissary' => 'Prepared foods and ingredients',
            'other' => 'Variable',
            default => 'Standard food service products',
        };
    }
    
    /**
     * Validate distributor options data structure
     */
    private function validateDistributorOptionsData(array $data): bool
    {
        // For development environment with enhanced data
        if (app()->environment(['local', 'development', 'staging'])) {
            $validator = Validator::make(['distributor_options' => $data], [
                'distributor_options' => 'required|array',
                'distributor_options.*.label' => 'required|string|max:255',
                'distributor_options.*.demo_description' => 'string',
                'distributor_options.*.typical_delivery_frequency' => 'string',
                'distributor_options.*.volume_capacity' => 'string',
                'distributor_options.*.geographic_coverage' => 'string',
                'distributor_options.*.pricing_model' => 'string',
                'distributor_options.*.product_range' => 'string',
            ]);
        } else {
            // For production environment with simple strings
            $validator = Validator::make(['distributor_options' => $data], [
                'distributor_options' => 'required|array',
                'distributor_options.*' => 'required|string|max:255',
            ]);
        }
        
        if ($validator->fails()) {
            $this->command->error('Distributor options validation failed:');
            foreach ($validator->errors()->all() as $error) {
                $this->command->error("  - {$error}");
            }
            return false;
        }
        
        return true;
    }
}