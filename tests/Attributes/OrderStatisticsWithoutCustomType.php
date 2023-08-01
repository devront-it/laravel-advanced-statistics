<?php

namespace Devront\AdvancedStatistics\Tests\Attributes;

use Devront\AdvancedStatistics\Attributes\AdvancedStatisticsAttribute;
use Devront\AdvancedStatistics\Attributes\Param;
use Devront\AdvancedStatistics\Statistics;

#[AdvancedStatisticsAttribute]
class OrderStatisticsWithoutCustomType extends Statistics
{
    #[Param]
    public string $country;

    #[Param]
    public string $source;

}
