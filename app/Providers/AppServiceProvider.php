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
    public function boot(): void
    {
        // The app is Bootstrap 5 (no Tailwind). Laravel's default paginator
        // emits Tailwind markup whose chevron SVGs render unconstrained without
        // Tailwind CSS, producing giant arrows. Use Bootstrap markup globally.
        Paginator::defaultView('pagination::bootstrap-5');
        Paginator::defaultSimpleView('pagination::simple-bootstrap-5');
    }
}
