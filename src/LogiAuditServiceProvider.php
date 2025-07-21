<?php

namespace AuroraWebSoftware\LogiAudit;

use AuroraWebSoftware\LogiAudit\Commands\PruneHistoryCommand;
use AuroraWebSoftware\LogiAudit\Commands\PruneLogsCommand;
use Illuminate\Support\ServiceProvider;
use Spatie\LaravelPackageTools\Package;

class LogiAuditServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any package services.
     */
    public function boot()
    {
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');

        $this->publishes([
            __DIR__.'/../config/logiaudit.php' => config_path('logiaudit.php'),
        ], 'config');

        if ($this->app->runningInConsole()) {
            $this->commands([
                PruneLogsCommand::class,
                PruneHistoryCommand::class,
            ]);
        }
    }

    /**
     * Register any package services.
     */
    public function register() {
        $this->mergeConfigFrom(
            __DIR__.'/../config/logiaudit.php', 'logiaudit'
        );
    }

    public function configurePackage(Package $package): void
    {
        /*
         * This class is a Package Service Provider
         *
         * More info: https://github.com/spatie/laravel-package-tools
         */
        $package
            ->name('logiaudit')
            ->hasConfigFile('logiaudit')
            ->hasViews();
        // ->hasMigration('')
        // ->hasCommand(::class);

    }
}
