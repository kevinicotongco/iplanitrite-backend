<?php

declare(strict_types=1);

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // AuthenticatedUser instances are bound in BindAuthenticatedUser middleware
        // after authentication, using app()->instance()
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
