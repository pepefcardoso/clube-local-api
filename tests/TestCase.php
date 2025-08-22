<?php

namespace Tests;

use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        if (in_array('Illuminate\Foundation\Testing\RefreshDatabase', class_uses_recursive($this))) {
            $this->seed(RolePermissionSeeder::class);
        }
    }
}
