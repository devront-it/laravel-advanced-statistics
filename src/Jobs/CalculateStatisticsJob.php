<?php

namespace Devront\AdvancedStatistics\Jobs;

use Devront\AdvancedStatistics\AdvancedStatistics;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class CalculateStatisticsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct()
    {
    }

    public function handle()
    {
        app(AdvancedStatistics::class)->getModelClass()->calculateMonthlyStatistics();
    }
}
