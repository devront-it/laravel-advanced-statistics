<?php

namespace Devront\AdvancedStatistics;

use Devront\AdvancedStatistics\Commands\AdvancedStatisticsRunCommand;
use Devront\AdvancedStatistics\Commands\CreateIdeHelperFileCommand;
use Illuminate\Contracts\Foundation\Application;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class StatisticsServiceProvider extends PackageServiceProvider
{

    public function configurePackage(Package $package): void
    {
        $package
            ->name('advanced-statistics')
            ->hasCommands(
                AdvancedStatisticsRunCommand::class,
                CreateIdeHelperFileCommand::class
            )
            ->hasMigration('create_statistics_tables');

        $this->app->singleton(AdvancedStatistics::class, fn(Application $app) => new AdvancedStatistics);
    }
}
