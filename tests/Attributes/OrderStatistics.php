<?php

namespace Devront\AdvancedStatistics\Tests\Attributes;

use Devront\AdvancedStatistics\Attributes\AdvancedStatisticsAttribute;
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
class OrderStatistics extends Statistics
{
    #[Param]
    public string $source;

    #[Param]
    public string $country;
}
