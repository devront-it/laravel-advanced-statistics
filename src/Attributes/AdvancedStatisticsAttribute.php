<?php

namespace Devront\AdvancedStatistics\Attributes;

#[\Attribute(\Attribute::TARGET_CLASS)]
class AdvancedStatisticsAttribute
{
    public function __construct(
        public ?string $type = null,
        public int     $keepDailyStatisticsForDays = 90,
        public int     $keepMonthlyStatisticsForMonths = 24
    )
    {
    }
}
