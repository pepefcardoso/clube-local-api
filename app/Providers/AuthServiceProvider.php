<?php

namespace App\Providers;

use App\Models\BusinessUser;
use App\Models\Customer;
use App\Models\StaffUser;
use Illuminate\Support\Facades\Gate;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;

class AuthServiceProvider extends ServiceProvider
{
    protected $policies = [
        //
    ];

    public function boot(): void
    {
        Gate::define('manage-customers', function ($user) {
            return $user instanceof StaffUser;
        });

        Gate::define('manage-business-users', function ($user) {
            if ($user instanceof StaffUser) {
                return true;
            }
            return $user instanceof BusinessUser && $user->hasRole('company.manager');
        });

        Gate::define('manage-customers', function ($user) {
            return $user instanceof StaffUser;
        });

        Gate::define('manage-business-users', function ($user) {
            if ($user instanceof StaffUser) {
                return true;
            }

            return $user instanceof BusinessUser && $user->hasRole('company.manager');
        });

        Gate::define('manage-staff-users', function ($user) {
            return $user instanceof StaffUser && $user->hasRole('internal.admin');
        });

        Gate::define('customer:premium', function ($user) {
            return $user instanceof Customer && $user->isPremium();
        });

        Gate::define('business:manage', function ($user) {
            return $user instanceof BusinessUser && $user->isManager();
        });

        Gate::define('staff:admin', function ($user) {
            return $user instanceof StaffUser && $user->isAdmin();
        });

        Gate::define('system:manage', function ($user) {
            return $user instanceof StaffUser && $user->isAdmin();
        });

        $this->registerPolicies();
    }
}
