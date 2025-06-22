<?php

namespace Database\Seeders;

use App\Models\Principal;
use App\Models\ProductLine;
use Illuminate\Database\Seeder;

class PrincipalBrandSeeder extends Seeder
{
    /**
     * Seed the 11 major food service brands and their product lines.
     */
    public function run(): void
    {
        $foodServiceBrands = [
            [
                'name' => 'Sysco Corporation',
                'contact_name' => 'Sales Team',
                'email' => 'sales@sysco.com',
                'phone' => '1-800-SYSCO-1',
                'website' => 'https://www.sysco.com',
                'address' => '1390 Enclave Parkway, Houston, TX 77077',
                'notes' => 'World\'s largest food distributor serving 650,000+ clients worldwide with 320+ distribution facilities.',
                'product_lines' => [
                    ['name' => 'Fresh Produce', 'description' => 'Farm-fresh fruits and vegetables'],
                    ['name' => 'Protein & Seafood', 'description' => 'Premium meats, poultry, and seafood'],
                    ['name' => 'Dairy & Frozen', 'description' => 'Dairy products and frozen foods'],
                    ['name' => 'Beverages', 'description' => 'Complete beverage solutions'],
                ],
            ],
            [
                'name' => 'US Foods',
                'contact_name' => 'Customer Service',
                'email' => 'info@usfoods.com',
                'phone' => '1-800-USA-FOOD',
                'website' => 'https://www.usfoods.com',
                'address' => '9399 W Higgins Rd, Rosemont, IL 60018',
                'notes' => 'Second largest foodservice distributor in the US serving 300,000+ customers with 70+ distribution centers.',
                'product_lines' => [
                    ['name' => 'Restaurant Solutions', 'description' => 'Complete restaurant supply solutions'],
                    ['name' => 'Healthcare Nutrition', 'description' => 'Specialized nutrition for healthcare facilities'],
                    ['name' => 'Culinary Equipment', 'description' => 'Professional kitchen equipment and supplies'],
                ],
            ],
            [
                'name' => 'Performance Food Group (PFG)',
                'contact_name' => 'Sales Department',
                'email' => 'sales@pfgc.com',
                'phone' => '1-800-PFG-FOOD',
                'website' => 'https://www.pfgc.com',
                'address' => '12500 West Creek Parkway, Richmond, VA 23238',
                'notes' => 'Third largest foodservice distributor serving 150,000+ customers with customized solutions.',
                'product_lines' => [
                    ['name' => 'Broadline Distribution', 'description' => 'Full-line food distribution services'],
                    ['name' => 'Specialty Products', 'description' => 'Gourmet and specialty food items'],
                    ['name' => 'Convenience Store', 'description' => 'C-store focused product lines'],
                ],
            ],
            [
                'name' => 'Gordon Food Service (GFS)',
                'contact_name' => 'Customer Relations',
                'email' => 'customerservice@gfs.com',
                'phone' => '1-800-968-4164',
                'website' => 'https://www.gfs.com',
                'address' => '1300 Gezon Parkway SW, Wyoming, MI 49509',
                'notes' => 'Family-owned distributor since 1897 serving US and Canada with strong regional presence.',
                'product_lines' => [
                    ['name' => 'Restaurant Solutions', 'description' => 'Complete restaurant food solutions'],
                    ['name' => 'Healthcare Food', 'description' => 'Healthcare and senior living nutrition'],
                    ['name' => 'Educational Food', 'description' => 'K-12 and university food programs'],
                ],
            ],
            [
                'name' => 'McLane Company',
                'contact_name' => 'Business Development',
                'email' => 'info@mclaneco.com',
                'phone' => '1-254-298-5563',
                'website' => 'https://www.mclaneco.com',
                'address' => '4747 McLane Parkway, Temple, TX 76504',
                'notes' => 'Major distributor serving convenience stores, casual dining, and quick service restaurants.',
                'product_lines' => [
                    ['name' => 'Convenience Store', 'description' => 'C-store product distribution'],
                    ['name' => 'Quick Service', 'description' => 'Fast food and QSR solutions'],
                    ['name' => 'Beverage Distribution', 'description' => 'Complete beverage solutions'],
                ],
            ],
            [
                'name' => 'KeHE Distributors',
                'contact_name' => 'Natural Products Team',
                'email' => 'info@kehe.com',
                'phone' => '1-630-295-8555',
                'website' => 'https://www.kehe.com',
                'address' => '1090 Lakeside Drive, Romeoville, IL 60446',
                'notes' => 'Leading distributor of natural, organic, specialty, and fresh products with 19 distribution centers.',
                'product_lines' => [
                    ['name' => 'Natural & Organic', 'description' => 'Certified organic and natural products'],
                    ['name' => 'Specialty Foods', 'description' => 'Gourmet and specialty food items'],
                    ['name' => 'Fresh Products', 'description' => 'Fresh organic produce and dairy'],
                ],
            ],
            [
                'name' => 'Ben E. Keith Foods',
                'contact_name' => 'Regional Sales',
                'email' => 'info@benekeith.com',
                'phone' => '1-817-877-5800',
                'website' => 'https://www.benekeith.com',
                'address' => '601 E 7th St, Fort Worth, TX 76102',
                'notes' => 'Premium food and beverage distributor since 1906 with strong presence in Southern/Southwestern US.',
                'product_lines' => [
                    ['name' => 'Fine Dining', 'description' => 'Premium ingredients for upscale restaurants'],
                    ['name' => 'Beverage Program', 'description' => 'Complete bar and beverage solutions'],
                    ['name' => 'Casual Dining', 'description' => 'Full-service restaurant solutions'],
                ],
            ],
            [
                'name' => 'Shamrock Foods',
                'contact_name' => 'Western Sales Team',
                'email' => 'sales@shamrockfoods.com',
                'phone' => '1-602-233-3000',
                'website' => 'https://www.shamrockfoods.com',
                'address' => '2540 S 75th Ave, Phoenix, AZ 85043',
                'notes' => 'Western US distributor specializing in fresh and frozen foods with personalized customer support.',
                'product_lines' => [
                    ['name' => 'Fresh Foods', 'description' => 'Fresh produce and dairy products'],
                    ['name' => 'Frozen Solutions', 'description' => 'Frozen food distribution'],
                    ['name' => 'Protein Products', 'description' => 'Fresh and frozen protein options'],
                ],
            ],
            [
                'name' => 'Restaurant Depot',
                'contact_name' => 'Membership Services',
                'email' => 'memberservices@restaurantdepot.com',
                'phone' => '1-718-236-3838',
                'website' => 'https://www.restaurantdepot.com',
                'address' => '1055 Stewart Ave, Westbury, NY 11590',
                'notes' => 'Members-only wholesale club providing bulk purchasing at wholesale prices for foodservice operators.',
                'product_lines' => [
                    ['name' => 'Bulk Foods', 'description' => 'Wholesale bulk food products'],
                    ['name' => 'Restaurant Supplies', 'description' => 'Kitchen equipment and supplies'],
                    ['name' => 'Paper Products', 'description' => 'Disposables and paper goods'],
                ],
            ],
            [
                'name' => 'Compass Group USA',
                'contact_name' => 'Contract Services',
                'email' => 'info@compass-usa.com',
                'phone' => '1-980-594-2900',
                'website' => 'https://www.compass-usa.com',
                'address' => '2400 Yorkmont Rd, Charlotte, NC 28217',
                'notes' => 'Largest foodservice management company globally serving colleges, healthcare, business dining, and sports venues.',
                'product_lines' => [
                    ['name' => 'Corporate Dining', 'description' => 'Business and corporate food services'],
                    ['name' => 'Healthcare Food', 'description' => 'Patient and staff dining solutions'],
                    ['name' => 'Education Dining', 'description' => 'K-12 and university food programs'],
                    ['name' => 'Sports & Leisure', 'description' => 'Stadium and venue concessions'],
                ],
            ],
            [
                'name' => 'Aramark Corporation',
                'contact_name' => 'Food Services Division',
                'email' => 'foodservices@aramark.com',
                'phone' => '1-215-238-3000',
                'website' => 'https://www.aramark.com',
                'address' => '2400 Market St, Philadelphia, PA 19103',
                'notes' => 'Major contract foodservice management company since 1959 serving business, healthcare, education, and leisure segments.',
                'product_lines' => [
                    ['name' => 'Business Services', 'description' => 'Corporate food and facility services'],
                    ['name' => 'Healthcare Services', 'description' => 'Patient care and staff dining'],
                    ['name' => 'Education Services', 'description' => 'Campus dining and retail solutions'],
                    ['name' => 'Corrections Food', 'description' => 'Correctional facility food services'],
                ],
            ],
        ];

        foreach ($foodServiceBrands as $brandData) {
            $productLines = $brandData['product_lines'];
            unset($brandData['product_lines']);

            $principal = Principal::firstOrCreate(
                ['name' => $brandData['name']],
                $brandData
            );

            foreach ($productLines as $lineData) {
                ProductLine::firstOrCreate(
                    [
                        'principal_id' => $principal->id,
                        'name' => $lineData['name']
                    ],
                    array_merge($lineData, ['is_active' => true])
                );
            }
        }

        $this->command->info('✅ Successfully seeded 11 major food service brands with their product lines');
        $this->command->info('📊 Total: ' . Principal::count() . ' principals and ' . ProductLine::count() . ' product lines');
    }
}