<?php

use Devront\AdvancedStatistics\AdvancedStatistics;

if (!function_exists('statistics')) {
    function statistics(): AdvancedStatistics
    {
        return app(AdvancedStatistics::class);
    }
}
