<?php

namespace App\Providers;

use Illuminate\Pagination\Paginator;
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
    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Pakai layout pagination Bootstrap 5 bawaan Laravel
        Paginator::useBootstrapFive();
        // Kalau mau Bootstrap 4:
        // Paginator::useBootstrapFour();
    }
}
