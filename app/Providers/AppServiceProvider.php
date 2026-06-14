<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\URL;

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
        if (config('app.env') === 'production' || true) {
            URL::forceScheme('https');
        }

        if (isset($_SERVER['HTTP_HOST']) && $_SERVER['HTTP_HOST'] === 'sie-akip.trenggalekkab.go.id') {
            config(['app.url' => 'https://sie-akip.trenggalekkab.go.id']);
        }
    }
}
