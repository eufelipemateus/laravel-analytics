<?php

namespace AndreasElia\Analytics;

use AndreasElia\Analytics\Http\Middleware\Analytics;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use AndreasElia\Analytics\Console\UpdatePagesViewSstatitics;

class AnalyticsServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->commands([
                UpdatePagesViewSstatitics::class,
            ]);
    
            $this->publishes([
                __DIR__.'/../config/analytics.php' => config_path('analytics.php'),
            ], 'analytics-config');

            $this->publishes([
                __DIR__.'/../resources/views' => resource_path('views/vendor/analytics'),
            ], 'analytics-assets');
        }

        // Migrations
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');

        // Middleware
        Route::middlewareGroup('analytics', [
            Analytics::class,
        ]);

        // Routes
        Route::group($this->routeConfig(), function () {
            $this->loadRoutesFrom(__DIR__.'/../routes/web.php');
        });

        // Views
        $this->loadViewsFrom(__DIR__.'/../resources/views', 'analytics');
    }

    protected function routeConfig(): array
    {
        return [
            'namespace' => 'AndreasElia\Analytics\Http\Controllers',
            'prefix' => config('analytics.prefix'),
            'middleware' => config('analytics.middleware'),
            'domain' => config('analytics.domain'),
        ];
    }

    public function register(): void
    {
        $this->mergeConfigFrom(
            __DIR__.'/../config/analytics.php',
            'analytics'
        );
    }
}
