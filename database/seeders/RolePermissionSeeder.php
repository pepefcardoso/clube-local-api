<?php

namespace Database\Seeders;

use App\Enums\BusinessUserRole;
use App\Enums\CustomerRole;
use App\Enums\StaffRole;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class RolePermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // Create permissions for each entity
        $permissions = [
            // Customer permissions
            'view customers',
            'create customers',
            'update customers',
            'delete customers',
            'manage customer subscriptions',

            // Business user permissions
            'view business users',
            'create business users',
            'update business users',
            'delete business users',
            'manage business user roles',
            'manage business user status',

            // Staff user permissions
            'view staff users',
            'create staff users',
            'update staff users',
            'delete staff users',
            'manage staff user roles',
            'manage staff user status',
            'manage system',

            // General permissions
            'view own profile',
            'update own profile',
        ];

        foreach ($permissions as $permission) {
            Permission::create(['name' => $permission]);
        }

        // Create Customer Roles
        $customerBasic = Role::create(['name' => CustomerRole::BASIC->value]);
        $customerPremium = Role::create(['name' => CustomerRole::PREMIUM->value]);

        // Assign permissions to customer roles
        $customerBasic->givePermissionTo([
            'view own profile',
            'update own profile',
        ]);

        $customerPremium->givePermissionTo([
            'view own profile',
            'update own profile',
            'manage customer subscriptions', // Premium customers can manage their own subscription
        ]);

        // Create Business User Roles
        $businessEmployee = Role::create(['name' => BusinessUserRole::EMPLOYEE->value]);
        $businessManager = Role::create(['name' => BusinessUserRole::MANAGER->value]);

        // Assign permissions to business user roles
        $businessEmployee->givePermissionTo([
            'view own profile',
            'update own profile',
        ]);

        $businessManager->givePermissionTo([
            'view own profile',
            'update own profile',
            'view business users',
            'create business users',
            'update business users',
            'delete business users',
            'manage business user status',
        ]);

        // Create Staff Roles
        $staffSupport = Role::create(['name' => StaffRole::SUPPORT->value]);
        $staffAdmin = Role::create(['name' => StaffRole::ADMIN->value]);

        // Assign permissions to staff roles
        $staffSupport->givePermissionTo([
            'view own profile',
            'update own profile',
            'view customers',
            'update customers',
            'view business users',
            'update business users',
            'view staff users',
        ]);

        $staffAdmin->givePermissionTo([
            'view own profile',
            'update own profile',
            // Full customer management
            'view customers',
            'create customers',
            'update customers',
            'delete customers',
            'manage customer subscriptions',
            // Full business user management
            'view business users',
            'create business users',
            'update business users',
            'delete business users',
            'manage business user roles',
            'manage business user status',
            // Full staff management
            'view staff users',
            'create staff users',
            'update staff users',
            'delete staff users',
            'manage staff user roles',
            'manage staff user status',
            'manage system',
        ]);

        $this->command->info('Roles and permissions seeded successfully!');
        $this->command->info('Created roles:');
        $this->command->info('- Customer: basic, premium');
        $this->command->info('- Business: employee, manager');
        $this->command->info('- Staff: support, admin');
    }
}
