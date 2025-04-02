<?php

namespace AuroraWebSoftware\LogiAudit\Tests;

use AuroraWebSoftware\LogiAudit\LogiAuditServiceProvider;
use Illuminate\Database\Eloquent\Factories\Factory;
use Orchestra\Testbench\TestCase as Orchestra;

class TestCase extends Orchestra
{
    protected function setUp(): void
    {
        parent::setUp();

        Factory::guessFactoryNamesUsing(
            fn (string $modelName) => 'AuroraWebSoftware\\LogiAudit\\Database\\Factories\\'.class_basename($modelName).'Factory'
        );
    }

    protected function getPackageProviders($app)
    {
        return [
            LogiAuditServiceProvider::class,
        ];
    }

    public function getEnvironmentSetUp($app)
    {

        // for GitHub tests wirh mysql
        // config()->set('database.default', 'mysql');

        // for local tests with sqlite
        // config()->set('database.default', 'testing');

        // for local tests with mysql
        // config()->set('database.default', 'mysql');

        // $migration = include __DIR__.'/../database/migrations/create_flexyfield_table.php';
        // $migration->up();

    }
}
