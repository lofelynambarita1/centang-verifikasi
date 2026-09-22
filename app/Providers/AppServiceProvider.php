<?php

namespace App\Providers;

use App\Models\User;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

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
        Gate::define('manage-audit-projects', fn (User $user): bool => $user->system_role->isAdminOrAbove());
        Gate::define('manage-templates', fn (User $user): bool => $user->system_role->isAdminOrAbove());
    }
}
