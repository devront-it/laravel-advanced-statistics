<?php

use Devront\AdvancedStatistics\Tests\Attributes\OrderStatistics;
use Devront\AdvancedStatistics\Tests\Attributes\OrderStatisticsWithoutCustomType;

it('can configure custom type', function () {
    $statistics = new OrderStatistics();
    expect($statistics->getType())->toBe('orders');
});

it('has default type if not explicitly set', function () {
    $statistics = new OrderStatisticsWithoutCustomType();
    expect($statistics->getType())->toBe(OrderStatisticsWithoutCustomType::class);
});

it('can configure custom statistics lifetime', function () {
    $statistics = new OrderStatistics();
    expect($statistics->getKeepDailyStatisticsForDays())->toBe(14);
    expect($statistics->getKeepMonthlyStatisticsForMonths())->toBe(12);
});
