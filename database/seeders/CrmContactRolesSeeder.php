<?php

namespace Database\Seeders;

use App\Models\SystemSetting;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CrmContactRolesSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info('👥 Seeding CRM Contact Roles...');
        
        $contactRoles = [
            'owner' => 'Owner',
            'manager' => 'Manager',
            'chef' => 'Executive Chef',
            'purchaser' => 'Purchasing Manager',
            'assistant' => 'Assistant Manager',
            'accountant' => 'Accountant',
            'other' => 'Other',
        ];
        
        DB::transaction(function () use ($contactRoles) {
            SystemSetting::updateOrCreate(
                ['key' => 'crm.contact_roles', 'category' => 'crm'],
                [
                    'value' => json_encode($contactRoles),
                    'type' => 'json',
                    'description' => 'Available contact roles for CRM contacts',
                    'default_value' => json_encode($contactRoles),
                    'validation_rules' => json_encode(['required', 'json']),
                    'ui_component' => 'json_editor',
                    'is_public' => false,
                    'sort_order' => 30,
                ]
            );
        });
        
        $this->command->info('✅ Contact Roles seeded successfully');
    }
}