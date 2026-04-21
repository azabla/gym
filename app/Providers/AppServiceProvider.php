<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Schema; 

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
        // Set the default string length for migrations
    Schema::defaultStringLength(191); // 
    
       // This gives the super_admin role 'God Mode'
    Gate::before(function ($user, $string) {
        return $user->hasRole('super_admin') ? true : null;
    });
    }
}
