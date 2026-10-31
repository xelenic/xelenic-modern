<?php

namespace App\Providers;

use App\Models\User;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * The permissions that each open a part of the admin panel.
     */
    public const ADMIN_PERMISSIONS = ['view users', 'manage roles', 'manage projects', 'manage clients'];

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
        // Admins pass every permission check (including Spatie's `permission`
        // middleware and @can), so they can never be locked out by editing
        // role permissions.
        Gate::before(fn (User $user) => $user->hasRole('admin') ? true : null);

        // The admin panel (/admin) is for anyone with at least one of its
        // permissions; each page inside still checks its own.
        Gate::define('access-admin', fn (User $user) => $user->canAny(self::ADMIN_PERMISSIONS));
    }
}
