<?php

namespace Devront\AdvancedStatistics\Tests;

use Devront\AdvancedStatistics\AdvancedStatistics;
use Devront\AdvancedStatistics\StatisticsServiceProvider;
use Devront\AdvancedStatistics\Tests\Attributes\OrderStatistics;
use Illuminate\Database\Eloquent\Factories\Factory;
use Orchestra\Testbench\TestCase as Orchestra;

class TestCase extends Orchestra
{
    protected function setUp(): void
    {
        parent::setUp();

        Factory::guessFactoryNamesUsing(
            fn(string $modelName) => 'Devront\\AdvancedStatistics\\Tests\\Database\\Factories\\' . class_basename($modelName) . 'Factory'
        );

        Factory::factoryForModel('User')->count(10)->create();
        Factory::factoryForModel('Order')->count(100)->create();

        app(AdvancedStatistics::class)->useStatistics([OrderStatistics::class]);
    }

    protected function getPackageProviders($app)
    {
        return [
            StatisticsServiceProvider::class,
        ];
    }

    public function getEnvironmentSetUp($app)
    {
        config()->set('database.default', 'testing');

        $migration = include __DIR__ . '/../database/migrations/create_statistics_tables.php';
        $migration->up();

        $migration = include __DIR__ . '/database/migrations/1_create_users_table.php';
        $migration->up();

        $migration = include __DIR__ . '/database/migrations/2_create_orders_table.php';
        $migration->up();
    }
}
