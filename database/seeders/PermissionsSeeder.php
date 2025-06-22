<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use App\Models\User;

class PermissionsSeeder extends Seeder
{
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        // Create permissions for CRM entities
        $permissions = [
            // Dashboard & Analytics
            'view dashboard',
            'view analytics',
            'export reports',
            
            // Organizations
            'view organizations',
            'create organizations',
            'edit organizations',
            'delete organizations',
            
            // Contacts
            'view contacts',
            'create contacts', 
            'edit contacts',
            'delete contacts',
            
            // Interactions
            'view interactions',
            'create interactions',
            'edit interactions',
            'delete interactions',
            
            // Opportunities
            'view opportunities',
            'create opportunities',
            'edit opportunities',
            'delete opportunities',
            
            // Leads
            'view leads',
            'create leads',
            'edit leads',
            'delete leads',
            'assign leads',
            
            // Contracts
            'view contracts',
            'create contracts',
            'edit contracts',
            'delete contracts',
            'approve contracts',
            
            // Principals & Product Lines
            'view principals',
            'create principals',
            'edit principals',
            'delete principals',
            'view product lines',
            'create product lines',
            'edit product lines',
            'delete product lines',
            
            // User Management (Admin only)
            'view users',
            'create users',
            'edit users',
            'delete users',
            'manage roles',
            'manage permissions',
            
            // System Settings
            'view system settings',
            'edit system settings',
            'manage system',
            
            // Advanced Features
            'view audit logs',
            'export data',
            'import data',
            'bulk operations',
        ];

        foreach ($permissions as $permission) {
            Permission::create(['name' => $permission]);
        }

        // Create roles and assign permissions
        $roles = [
            'Super Admin' => Permission::all(),
            'Admin' => [
                'view dashboard', 'view analytics', 'export reports',
                'view organizations', 'create organizations', 'edit organizations',
                'view contacts', 'create contacts', 'edit contacts',
                'view interactions', 'create interactions', 'edit interactions',
                'view opportunities', 'create opportunities', 'edit opportunities',
                'view leads', 'create leads', 'edit leads', 'assign leads',
                'view contracts', 'create contracts', 'edit contracts', 'approve contracts',
                'view principals', 'create principals', 'edit principals',
                'view product lines', 'create product lines', 'edit product lines',
                'view users', 'create users', 'edit users',
                'view system settings', 'edit system settings',
                'view audit logs', 'export data', 'import data', 'bulk operations',
            ],
            'Sales Manager' => [
                'view dashboard', 'view analytics', 'export reports',
                'view organizations', 'create organizations', 'edit organizations',
                'view contacts', 'create contacts', 'edit contacts',
                'view interactions', 'create interactions', 'edit interactions',
                'view opportunities', 'create opportunities', 'edit opportunities',
                'view leads', 'create leads', 'edit leads', 'assign leads',
                'view contracts', 'create contracts', 'edit contracts',
                'view principals', 'view product lines',
                'view users', 'export data',
            ],
            'Sales Rep' => [
                'view dashboard', 'view analytics',
                'view organizations', 'create organizations', 'edit organizations',
                'view contacts', 'create contacts', 'edit contacts',
                'view interactions', 'create interactions', 'edit interactions',
                'view opportunities', 'create opportunities', 'edit opportunities',
                'view leads', 'create leads', 'edit leads',
                'view contracts', 'create contracts', 'edit contracts',
                'view principals', 'view product lines',
            ],
            'Account Manager' => [
                'view dashboard', 'view analytics',
                'view organizations', 'edit organizations',
                'view contacts', 'create contacts', 'edit contacts',
                'view interactions', 'create interactions', 'edit interactions',
                'view opportunities', 'create opportunities', 'edit opportunities',
                'view contracts', 'create contracts', 'edit contracts',
                'view principals', 'view product lines',
            ],
            'Viewer' => [
                'view dashboard',
                'view organizations', 'view contacts', 'view interactions',
                'view opportunities', 'view leads', 'view contracts',
                'view principals', 'view product lines',
            ],
        ];

        foreach ($roles as $roleName => $rolePermissions) {
            $role = Role::create(['name' => $roleName]);
            
            if ($rolePermissions instanceof \Illuminate\Database\Eloquent\Collection) {
                $role->givePermissionTo($rolePermissions);
            } else {
                foreach ($rolePermissions as $permission) {
                    $role->givePermissionTo($permission);
                }
            }
        }

        // Create default admin user if not exists
        $adminUser = User::where('email', 'admin@pantrycrm.test')->first();
        if (!$adminUser) {
            $adminUser = User::create([
                'name' => 'System Administrator',
                'email' => 'admin@pantrycrm.test',
                'password' => bcrypt('password123'),
                'email_verified_at' => now(),
            ]);
        }
        
        $adminUser->assignRole('Super Admin');

        // Create sample users for testing
        $users = [
            ['name' => 'Sales Manager', 'email' => 'sales.manager@pantrycrm.test', 'role' => 'Sales Manager'],
            ['name' => 'John Sales Rep', 'email' => 'john.rep@pantrycrm.test', 'role' => 'Sales Rep'],
            ['name' => 'Jane Account Manager', 'email' => 'jane.account@pantrycrm.test', 'role' => 'Account Manager'],
            ['name' => 'Bob Viewer', 'email' => 'bob.viewer@pantrycrm.test', 'role' => 'Viewer'],
        ];

        foreach ($users as $userData) {
            $user = User::where('email', $userData['email'])->first();
            if (!$user) {
                $user = User::create([
                    'name' => $userData['name'],
                    'email' => $userData['email'],
                    'password' => bcrypt('password123'),
                    'email_verified_at' => now(),
                ]);
            }
            
            $user->assignRole($userData['role']);
        }

        $this->command->info('Permissions and roles created successfully!');
        $this->command->info('Admin user: admin@pantrycrm.test / password123');
        $this->command->info('Sample users created with various roles.');
    }
}