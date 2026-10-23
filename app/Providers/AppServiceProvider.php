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
        // Admins pass every permission check (including Spatie's `permission`
        // middleware and @can), so they can never be locked out by editing
        // role permissions.
        Gate::before(fn (User $user) => $user->hasRole('admin') ? true : null);
    }
}
