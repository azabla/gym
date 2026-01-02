<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Gate;

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
       // This gives the super_admin role 'God Mode'
    Gate::before(function ($user, $string) {
        return $user->hasRole('super_admin') ? true : null;
    });
    }
}
