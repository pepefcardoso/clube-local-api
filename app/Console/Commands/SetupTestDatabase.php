<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;

class SetupTestDatabase extends Command
{
    protected $signature = 'test:setup';
    protected $description = 'Setup test database with migrations and seeds';

    public function handle()
    {
        $this->info('Setting up test database...');

        Artisan::call('migrate:fresh', [
            '--env' => 'testing',
            '--force' => true,
        ]);

        $this->info('Migrations completed.');

        Artisan::call('db:seed', [
            '--env' => 'testing',
            '--class' => 'Database\\Seeders\\RolePermissionSeeder',
        ]);

        $this->info('Seeds completed.');
        $this->info('Test database setup complete!');

        return 0;
    }
}
