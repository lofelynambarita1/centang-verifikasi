<?php

namespace App\Providers;

use App\Models\Audit;
use App\Models\User;
use Illuminate\Contracts\View\View as ViewContract;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\View;
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
        Gate::define('update-audit-summary', fn (User $user, Audit $audit): bool => $user->isAdmin()
            || $audit->userIsLead($user));

        View::composer('layouts.app', function (ViewContract $view): void {
            $view->with('unreadNotificationCount', auth()->user()?->unreadNotifications()->count() ?? 0);
        });
    }
}
