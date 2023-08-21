<?php

namespace Devront\AdvancedStatistics\Tests\Attributes;

use Devront\AdvancedStatistics\Attributes\AdvancedStatisticsAttribute;
use Devront\AdvancedStatistics\Attributes\Param;
use Devront\AdvancedStatistics\Statistics;

#[AdvancedStatisticsAttribute(
    type: 'items',
    keepDailyStatisticsForDays: 14,
    keepMonthlyStatisticsForMonths: 12
)]
/**
 * @method self forSource() forSource(string $value)
 */
class ItemStatistics extends Statistics
{
    #[Param]
    protected string $sku;

    #[Param]
    protected string $country;
}
