<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Customer;
use App\Models\BusinessUser;
use App\Models\StaffUser;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Seed roles and permissions first
        $this->call(RolePermissionSeeder::class);

        // Create sample customers
        $basicCustomer = Customer::create([
            'name' => 'João Silva',
            'email' => 'joao@customer.com',
            'password' => Hash::make('password123'),
            'phone' => '(48) 99999-1234',
        ]);

        $premiumCustomer = Customer::create([
            'name' => 'Maria Santos',
            'email' => 'maria@customer.com',
            'password' => Hash::make('password123'),
            'phone' => '(48) 99999-5678',
        ]);

        // Create sample business users
        $employee = BusinessUser::create([
            'name' => 'Carlos Oliveira',
            'email' => 'carlos@empresa.com',
            'password' => Hash::make('password123'),
            'phone' => '(48) 99999-9012'
        ]);

        $manager = BusinessUser::create([
            'name' => 'Ana Costa',
            'email' => 'ana@empresa.com',
            'password' => Hash::make('password123'),
            'phone' => '(48) 99999-3456'
        ]);

        // Create sample staff users
        $support = StaffUser::create([
            'name' => 'Pedro Suporte',
            'email' => 'pedro@staff.com',
            'password' => Hash::make('password123'),
            'phone' => '(48) 99999-7890',
        ]);

        $admin = StaffUser::create([
            'name' => 'Roberto Admin',
            'email' => 'roberto@staff.com',
            'password' => Hash::make('password123'),
            'phone' => '(48) 99999-2468',
        ]);

        $this->command->info('Sample users created:');
        $this->command->info('Customers:');
        $this->command->info('- joao@customer.com (basic) - password123');
        $this->command->info('- maria@customer.com (premium) - password123');
        $this->command->info('Business Users:');
        $this->command->info('- carlos@empresa.com (employee) - password123');
        $this->command->info('- ana@empresa.com (manager) - password123');
        $this->command->info('Staff Users:');
        $this->command->info('- pedro@staff.com (support) - password123');
        $this->command->info('- roberto@staff.com (admin) - password123');
    }
}