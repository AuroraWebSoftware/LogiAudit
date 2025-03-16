<?php

namespace AuroraWebSoftware\LogiAudit;

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

        if ($this->app->runningInConsole()) {
            $this->commands([
                PruneLogsCommand::class,
            ]);
        }

    }

    /**
     * Register any package services.
     */
    public function register()
    {
        // Register package stuff
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
        // ->hasMigration('create_arflow_history_table')
        // ->hasCommand(ArFlowCommand::class);

    }
}
