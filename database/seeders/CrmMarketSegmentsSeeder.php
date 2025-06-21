<?php

namespace Database\Seeders;

use App\Models\SystemSetting;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class CrmMarketSegmentsSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed CRM market segments configuration.
     * 
     * Creates market segments for CRM organization categorization.
     * Uses idempotent operations to prevent duplicates.
     */
    public function run(): void
    {
        $this->command->info('🏪 Seeding CRM Market Segments...');
        
        // Track timing for performance monitoring
        $startTime = microtime(true);
        
        // Market segments configuration with environment-specific variations
        $marketSegments = $this->getMarketSegmentsData();
        
        // Validate data before seeding
        if (!$this->validateMarketSegmentsData($marketSegments)) {
            $this->command->error('❌ Market segments data validation failed');
            return;
        }
        
        DB::transaction(function () use ($marketSegments) {
            // Use updateOrCreate for idempotent seeding
            SystemSetting::updateOrCreate(
                [
                    'key' => 'crm.market_segments',
                    'category' => 'crm'
                ],
                [
                    'value' => json_encode($marketSegments),
                    'type' => 'json',
                    'description' => 'Available market segments for CRM organization categorization',
                    'default_value' => json_encode($this->getDefaultMarketSegments()),
                    'validation_rules' => json_encode(['required', 'json']),
                    'ui_component' => 'json_editor',
                    'is_public' => false,
                    'sort_order' => 50,
                ]
            );
        });
        
        $endTime = microtime(true);
        $duration = round($endTime - $startTime, 2);
        
        $this->command->info("✅ Market Segments seeded successfully in {$duration} seconds");
    }
    
    /**
     * Get market segments data based on environment
     */
    private function getMarketSegmentsData(): array
    {
        $baseMarketSegments = $this->getDefaultMarketSegments();
        
        // Environment-specific customizations
        if (app()->environment('production')) {
            // Production: Use standard market segments
            return $baseMarketSegments;
        } elseif (app()->environment(['local', 'development', 'staging'])) {
            // Development: Add demo context and examples
            return array_map(function ($segment, $key) {
                return [
                    'label' => $segment,
                    'demo_description' => $this->getDemoDescription($key),
                    'typical_check_average' => $this->getTypicalCheckAverage($key),
                    'volume_potential' => $this->getVolumePotential($key),
                    'service_style' => $this->getServiceStyle($key),
                    'prime_contact_role' => $this->getPrimeContactRole($key),
                ];
            }, $baseMarketSegments, array_keys($baseMarketSegments));
        } else {
            // Testing: Minimal data for consistency
            return $baseMarketSegments;
        }
    }
    
    /**
     * Get default market segments configuration
     */
    private function getDefaultMarketSegments(): array
    {
        return [
            'fine_dining' => 'Fine Dining',
            'casual_dining' => 'Casual Dining',
            'quick_service' => 'Quick Service (QSR)',
            'fast_casual' => 'Fast Casual',
            'catering' => 'Catering Services',
            'institutional' => 'Institutional Dining',
            'hotel_restaurant' => 'Hotel Restaurant',
            'food_truck' => 'Food Truck',
            'brewery_distillery' => 'Brewery/Distillery',
            'specialty_retail' => 'Specialty Food Retail',
            'corporate_dining' => 'Corporate Dining',
            'healthcare' => 'Healthcare Dining',
            'education' => 'Educational Dining',
            'other' => 'Other',
        ];
    }
    
    /**
     * Get demo description for development environments
     */
    private function getDemoDescription(string $key): string
    {
        return match($key) {
            'fine_dining' => 'Upscale restaurants with full service, tableside service, and premium ingredients',
            'casual_dining' => 'Mid-scale restaurants with table service and moderate pricing',
            'quick_service' => 'Fast food restaurants with counter service and quick turnaround',
            'fast_casual' => 'Higher quality quick service with customizable options and fresh ingredients',
            'catering' => 'Off-site catering services for events, meetings, and special occasions',
            'institutional' => 'Large-scale food service for schools, hospitals, and government facilities',
            'hotel_restaurant' => 'Restaurants and food service within hotel properties',
            'food_truck' => 'Mobile food service operations with limited menu and space',
            'brewery_distillery' => 'Craft breweries and distilleries with food service components',
            'specialty_retail' => 'Gourmet markets, delis, and specialty food retailers',
            'corporate_dining' => 'Employee dining facilities and corporate cafeterias',
            'healthcare' => 'Hospital, clinic, and medical facility food service',
            'education' => 'School cafeterias, university dining halls, and campus food service',
            'other' => 'Any other type of food service operation',
            default => 'Standard food service segment',
        };
    }
    
    /**
     * Get typical check average for demo purposes (USD)
     */
    private function getTypicalCheckAverage(string $key): array
    {
        return match($key) {
            'fine_dining' => ['min' => 50, 'max' => 150, 'average' => 85],
            'casual_dining' => ['min' => 15, 'max' => 40, 'average' => 25],
            'quick_service' => ['min' => 5, 'max' => 15, 'average' => 9],
            'fast_casual' => ['min' => 8, 'max' => 18, 'average' => 12],
            'catering' => ['min' => 10, 'max' => 25, 'average' => 15],
            'institutional' => ['min' => 3, 'max' => 8, 'average' => 5],
            'hotel_restaurant' => ['min' => 20, 'max' => 80, 'average' => 45],
            'food_truck' => ['min' => 6, 'max' => 16, 'average' => 10],
            'brewery_distillery' => ['min' => 12, 'max' => 30, 'average' => 18],
            'specialty_retail' => ['min' => 8, 'max' => 25, 'average' => 15],
            'corporate_dining' => ['min' => 6, 'max' => 15, 'average' => 10],
            'healthcare' => ['min' => 4, 'max' => 12, 'average' => 7],
            'education' => ['min' => 3, 'max' => 10, 'average' => 6],
            'other' => ['min' => 5, 'max' => 25, 'average' => 12],
            default => ['min' => 5, 'max' => 25, 'average' => 12],
        };
    }
    
    /**
     * Get volume potential for demo purposes
     */
    private function getVolumePotential(string $key): string
    {
        return match($key) {
            'fine_dining' => 'Medium',        // Lower volume, higher margin
            'casual_dining' => 'High',        // Good volume and frequency
            'quick_service' => 'Very High',   // High volume, frequent orders
            'fast_casual' => 'High',          // High volume, good margins
            'catering' => 'Medium',           // Variable volume based on events
            'institutional' => 'Very High',   // Large volume, consistent
            'hotel_restaurant' => 'Medium',   // Depends on hotel occupancy
            'food_truck' => 'Low',            // Limited space and volume
            'brewery_distillery' => 'Medium', // Growing segment with potential
            'specialty_retail' => 'Medium',   // Specialty products, good margins
            'corporate_dining' => 'High',     // Consistent volume during work days
            'healthcare' => 'High',           // 24/7 operations, consistent volume
            'education' => 'Very High',       // Large volume during school year
            'other' => 'Medium',              // Variable
            default => 'Medium',
        };
    }
    
    /**
     * Get service style for demo purposes
     */
    private function getServiceStyle(string $key): string
    {
        return match($key) {
            'fine_dining' => 'Full Service',
            'casual_dining' => 'Table Service',
            'quick_service' => 'Counter Service',
            'fast_casual' => 'Counter/Self Service',
            'catering' => 'Catered Service',
            'institutional' => 'Cafeteria Style',
            'hotel_restaurant' => 'Mixed Service',
            'food_truck' => 'Walk-up Service',
            'brewery_distillery' => 'Bar Service',
            'specialty_retail' => 'Retail/Deli',
            'corporate_dining' => 'Cafeteria Style',
            'healthcare' => 'Tray Service/Cafeteria',
            'education' => 'Cafeteria Style',
            'other' => 'Various',
            default => 'Various',
        };
    }
    
    /**
     * Get prime contact role for demo purposes
     */
    private function getPrimeContactRole(string $key): string
    {
        return match($key) {
            'fine_dining' => 'Executive Chef',
            'casual_dining' => 'General Manager',
            'quick_service' => 'District Manager',
            'fast_casual' => 'Operations Manager',
            'catering' => 'Catering Manager',
            'institutional' => 'Food Service Director',
            'hotel_restaurant' => 'F&B Manager',
            'food_truck' => 'Owner/Operator',
            'brewery_distillery' => 'Kitchen Manager',
            'specialty_retail' => 'Purchasing Manager',
            'corporate_dining' => 'Facility Manager',
            'healthcare' => 'Nutrition Services Director',
            'education' => 'Food Service Director',
            'other' => 'Manager',
            default => 'Manager',
        };
    }
    
    /**
     * Validate market segments data structure
     */
    private function validateMarketSegmentsData(array $data): bool
    {
        // For development environment with enhanced data
        if (app()->environment(['local', 'development', 'staging'])) {
            $validator = Validator::make(['market_segments' => $data], [
                'market_segments' => 'required|array',
                'market_segments.*.label' => 'required|string|max:255',
                'market_segments.*.demo_description' => 'string',
                'market_segments.*.typical_check_average' => 'array',
                'market_segments.*.volume_potential' => 'string',
                'market_segments.*.service_style' => 'string',
                'market_segments.*.prime_contact_role' => 'string',
            ]);
        } else {
            // For production environment with simple strings
            $validator = Validator::make(['market_segments' => $data], [
                'market_segments' => 'required|array',
                'market_segments.*' => 'required|string|max:255',
            ]);
        }
        
        if ($validator->fails()) {
            $this->command->error('Market segments validation failed:');
            foreach ($validator->errors()->all() as $error) {
                $this->command->error("  - {$error}");
            }
            return false;
        }
        
        return true;
    }
}