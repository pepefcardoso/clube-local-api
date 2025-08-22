<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Gate;
use App\Models\Customer;
use App\Models\BusinessUser;
use App\Models\StaffUser;
use App\Policies\CustomerPolicy;
use App\Policies\BusinessUserPolicy;
use App\Policies\StaffUserPolicy;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Register policies
        Gate::policy(Customer::class, CustomerPolicy::class);
        Gate::policy(BusinessUser::class, BusinessUserPolicy::class);
        Gate::policy(StaffUser::class, StaffUserPolicy::class);

        // Define additional gates for cross-model permissions
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

        // Define ability-based gates for Sanctum
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
    }
}