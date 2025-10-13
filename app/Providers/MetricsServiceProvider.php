<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class MetricsServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        // Metrics are registered dynamically via the RequestMetrics middleware and controllers.
    }
}
