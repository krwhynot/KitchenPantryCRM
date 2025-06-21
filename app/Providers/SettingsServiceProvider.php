<?php

namespace App\Providers;

use App\Services\SettingsService;
use Illuminate\Support\ServiceProvider;
use Illuminate\Contracts\Foundation\Application;

class SettingsServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        // Register the SettingsService as a singleton
        $this->app->singleton(SettingsService::class, function (Application $app) {
            return new SettingsService(
                $app->make('cache'),
                $app->make('validator'),
                $app->make(\App\Models\SystemSetting::class)
            );
        });

        // Register facade alias
        $this->app->alias(SettingsService::class, 'settings');
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        // Warm cache with critical settings on application boot
        if (!$this->app->runningInConsole() && !$this->app->environment('testing')) {
            $this->app->afterResolving(SettingsService::class, function (SettingsService $settings) {
                try {
                    $settings->warmCache();
                } catch (\Exception $e) {
                    // Silently fail cache warming - don't break app boot
                    logger()->warning('Settings cache warming failed', ['error' => $e->getMessage()]);
                }
            });
        }
    }

    /**
     * Get the services provided by the provider.
     */
    public function provides(): array
    {
        return [
            SettingsService::class,
            'settings',
        ];
    }
}