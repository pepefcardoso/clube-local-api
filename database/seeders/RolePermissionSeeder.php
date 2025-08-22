<?php

namespace Database\Seeders;

use App\Enums\BusinessUserRole;
use App\Enums\CustomerRole;
use App\Enums\StaffRole;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RolePermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        $permissionsByGroup = [
            'profile' => [
                'view own profile',
                'update own profile',
            ],
            'customers' => [
                'view customers',
                'create customers',
                'update customers',
                'delete customers',
            ],
            'business_users' => [
                'view business users',
                'create business users',
                'update business users',
                'delete business users',
                'manage business user roles',
                'manage business user status',
            ],
            'staff_users' => [
                'view staff users',
                'create staff users',
                'update staff users',
                'delete staff users',
                'manage staff user roles',
                'manage staff user status',
            ],
            'system' => [
                'manage system',
            ],
        ];

        foreach ($permissionsByGroup as $group) {
            foreach ($group as $permission) {
                Permission::firstOrCreate(['name' => $permission]);
            }
        }

        Role::firstOrCreate(['name' => CustomerRole::BASIC->value])
            ->givePermissionTo($permissionsByGroup['profile']);

        Role::firstOrCreate(['name' => CustomerRole::PREMIUM->value])
            ->givePermissionTo($permissionsByGroup['profile']);

        Role::firstOrCreate(['name' => BusinessUserRole::EMPLOYEE->value])
            ->givePermissionTo($permissionsByGroup['profile']);

        Role::firstOrCreate(['name' => BusinessUserRole::MANAGER->value])
            ->givePermissionTo([
                ...$permissionsByGroup['profile'],
                'view business users',
                'create business users',
                'update business users',
                'delete business users',
                'manage business user status',
            ]);

        Role::firstOrCreate(['name' => StaffRole::SUPPORT->value])
            ->givePermissionTo([
                ...$permissionsByGroup['profile'],
                'view customers',
                'update customers',
                'view business users',
                'update business users',
                'view staff users',
            ]);

        Role::firstOrCreate(['name' => StaffRole::ADMIN->value])
            ->givePermissionTo(Permission::all());

        if ($this->command) {
            $this->command->info('✅ Roles and permissions seeded successfully!');
            $this->command->table(['Role Type', 'Roles Created'], [
                ['Customer', 'basic, premium'],
                ['Business', 'employee, manager'],
                ['Staff', 'support, admin'],
            ]);
        }
    }
}
