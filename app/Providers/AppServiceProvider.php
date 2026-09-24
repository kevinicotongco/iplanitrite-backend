<?php

declare(strict_types=1);

namespace App\Providers;

use App\Models\EventChecklist;
use App\Models\EventChecklistGroup;
use App\Models\Supplier;
use App\Observers\EventChecklistGroupObserver;
use App\Observers\EventChecklistObserver;
use App\Observers\SupplierObserver;
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
        // Register Eloquent observers for audit logging
        Supplier::observe(SupplierObserver::class);
        EventChecklistGroup::observe(EventChecklistGroupObserver::class);
        EventChecklist::observe(EventChecklistObserver::class);
    }
}
