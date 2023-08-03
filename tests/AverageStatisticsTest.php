<?php

use Carbon\Carbon;
use Devront\AdvancedStatistics\Tests\Attributes\OrderStatisticsWithAvg;
use Devront\AdvancedStatistics\Tests\Models\User;

it('can hit and get average statistics', function () {
    $user = User::first();
    $statistics = new OrderStatisticsWithAvg();

    $statistics
        ->for($user)
        ->forCountry('de')
        ->timeToFulfill(3)
        ->hit(2);


    $statistics = new OrderStatisticsWithAvg();
    $avg = $statistics
        ->for($user)
        ->forCountry('de')
        ->getAverageTimeToFulfill(1);

    expect($avg)->toBe(3.0);

    $statistics = new OrderStatisticsWithAvg();
    $statistics
        ->for($user)
        ->forCountry('gb')
        ->timeToFulfill(6)
        ->hit();


    $statistics = new OrderStatisticsWithAvg();
    $avg = $statistics
        ->for($user)
        ->getAverageTimeToFulfill(1);

    expect($avg)->toBe(4.0);
});

it('does not mess up averages if null is passed', function () {
    $model = app(Devront\AdvancedStatistics\AdvancedStatistics::class)->getModelClass();
    $user1 = User::find(1);

    $statistics = new OrderStatisticsWithAvg();
    $statistics
        ->for($user1)
        ->forCountry('de')
        ->timeToFulfill(2)
        ->hit(3);

    $statistics = new OrderStatisticsWithAvg();
    $statistics
        ->for($user1)
        ->forCountry('de')
        ->timeToFulfill(null)
        ->hit(3);

    $statistics = new OrderStatisticsWithAvg();
    expect(
        $statistics
            ->for($user1)
            ->forCountry('de')
            ->getAverageTimeToFulfill()
    )->toBe(2.0);

    expect($model::query()->timeframe('d')->count())->toBe(1);

});

it('can accumulate daily statistics with averages on monthly statistics', function () {
    $user = User::first();
    $statistics = new OrderStatisticsWithAvg();
    $model = app(Devront\AdvancedStatistics\AdvancedStatistics::class)->getModelClass();

    // today: 20.03.
    Carbon::setTestNow(Carbon::createFromFormat('d.m.Y', '20.03.2023'));

    $statistics
        ->for($user)
        ->forCountry('de')
        ->timeToFulfill(2)
        ->hit(3);

    $statistics
        ->for($user)
        ->forCountry('gb')
        ->timeToFulfill(3)
        ->hit(3);

    $statistics = new OrderStatisticsWithAvg();
    expect(
        $statistics
            ->for($user)
            ->getAverageTimeToFulfill(1)
    )
        ->toBe(2.5);

    // Keep for 14 days

    // new now = 03.04.
    Carbon::setTestNow(now()->addDays($statistics->getKeepDailyStatisticsForDays()));
    expect(now()->format('d.m.Y'))->toBe('03.04.2023');

    // Should have 2 db entry with 'd' timeframe
    expect($model::query()->timeframe('d')->count())->toBe(2);
    expect($model::query()->timeframe('m')->count())->toBe(0);

    $delete_older_than = now()->subDays($statistics->getKeepDailyStatisticsForDays() - 1)->startOfDay();
    expect($delete_older_than->format('d.m.Y'))->toBe('21.03.2023');

    // Do accumulation
    $model::calculateMonthlyStatistics();

    // Should still have 1 db entry, but with 'm' timeframe
    expect($model::query()->timeframe('m')->count())->toBe(2);
    expect($model::query()->timeframe('d')->count())->toBe(0);

    // Total for de should still be 3
    $statistics = new OrderStatisticsWithAvg();
    $res = $statistics
        ->for($user)
        ->forCountry('de')
        ->get();

    expect($res)->toBe(3.0);

    // Total for all countries should still be 6
    $statistics = new OrderStatisticsWithAvg();
    $res = $statistics
        ->for($user)
        ->forCountry('gb')
        ->get();

    expect($res)->toBe(3.0);

    $statistics = new OrderStatisticsWithAvg();
    expect(
        $statistics
            ->for($user)
            ->getAverageTimeToFulfill(1)
    )
        ->toBe(2.5);
});
