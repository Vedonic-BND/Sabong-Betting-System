<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Models\TellerCash;
use App\Models\Bet;
use App\Observers\TellerCashObserver;
use App\Observers\BetObserver;

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
        // Register model observers
        TellerCash::observe(TellerCashObserver::class);
        Bet::observe(BetObserver::class);
    }
}
