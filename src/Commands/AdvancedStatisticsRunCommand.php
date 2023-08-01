<?php

namespace Devront\AdvancedStatistics\Commands;

use Devront\AdvancedStatistics\Jobs\CalculateStatisticsJob;
use Illuminate\Console\Command;

class AdvancedStatisticsRunCommand extends Command
{
    protected $signature = 'advanced-statistics:run';

    protected $description = 'Dispatches the job for calculating, accumulating and cleaning up statistics.';

    public function handle()
    {
        CalculateStatisticsJob::dispatch();
    }
}
