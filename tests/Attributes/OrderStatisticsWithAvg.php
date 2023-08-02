<?php

namespace Devront\AdvancedStatistics\Tests\Attributes;

use Devront\AdvancedStatistics\Attributes\AdvancedStatisticsAttribute;
use Devront\AdvancedStatistics\Attributes\Avg;
use Devront\AdvancedStatistics\Attributes\Param;
use Devront\AdvancedStatistics\Statistics;

#[AdvancedStatisticsAttribute(
    type: 'orders',
    keepDailyStatisticsForDays: 14,
    keepMonthlyStatisticsForMonths: 12
)]
/**
 * @method self forSource() forSource(string $value)
 */
class OrderStatisticsWithAvg extends Statistics
{
    #[Param]
    protected string $source;

    #[Param]
    protected string $country;

    #[Avg]
    protected float $time_to_fulfill;
}
