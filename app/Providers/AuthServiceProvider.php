<?php

namespace App\Providers;

use Illuminate\Support\Facades\Gate;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;

class AuthServiceProvider extends ServiceProvider
{
    protected $policies = [
        \App\Models\Customer::class => \App\Policies\CustomerPolicy::class,
        \App\Models\BusinessUser::class => \App\Policies\BusinessUserPolicy::class,
        \App\Models\StaffUser::class => \App\Policies\StaffUserPolicy::class,
    ];

    public function boot(): void
    {
        $this->registerPolicies();

        // Gates simplificados - apenas para casos especiais
        Gate::define(
            'system:manage',
            fn($user) =>
            $user instanceof \App\Models\StaffUser && $user->hasRole('staff.admin')
        );
    }
}
