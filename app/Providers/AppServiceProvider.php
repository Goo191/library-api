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
        if (request()->isSecure() || str_contains(request()->getHost(), 'ngrok-free.app')) {
            URL::forceScheme('https');
            URL::forceRootUrl(request()->getSchemeAndHttpHost());
        } else {
            URL::forceRootUrl(config('app.url'));
        }
        
    }
}
