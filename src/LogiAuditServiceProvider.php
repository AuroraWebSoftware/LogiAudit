<?php

namespace AuroraWebSoftware\LogiAudit;

use AuroraWebSoftware\LogiAudit\Commands\PruneLogsCommand;
use Illuminate\Queue\DatabaseQueue;
use Illuminate\Support\Facades\Queue;
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

        //        Queue::extend('logiaudit_database', function () {
        //            return new DatabaseQueue(
        //                app('db'),
        //                config('logiaudit.queue.table'),
        //                config('queue.connections.database.retry_after', 90),
        //                config('queue.connections.database.after_commit', false)
        //            );
        //        });

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
