<?php

namespace App\Providers;

use App\Services\ResendMailService;
use Illuminate\Support\ServiceProvider;

class ResendMailServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        $this->app->singleton(ResendMailService::class, function ($app) {
            return new ResendMailService();
        });
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        //
    }
}
