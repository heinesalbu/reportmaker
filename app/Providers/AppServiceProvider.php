<?php

namespace App\Providers;

use Illuminate\Support\Facades\URL;
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
        // Denne linjen tvinger ALLE genererte URL-er til å bruke https.
        // Vi fjerner if-sjekken for 'production' siden din .env bruker 'local'.
        URL::forceScheme('https');
    }
}
