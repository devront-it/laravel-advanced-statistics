<?php

use Devront\AdvancedStatistics\AdvancedStatistics;
use Devront\AdvancedStatistics\Tests\Attributes\OrderStatistics;
use Devront\AdvancedStatistics\Tests\Models\User;
use Illuminate\Support\Carbon;

it('can accumulate daily statistics on monthly statistics', function () {
    $user = User::first();
    $statistics = new OrderStatistics();
    $model = app(AdvancedStatistics::class)->getModelClass();

    expect(app(AdvancedStatistics::class)->statistics)->toBe([OrderStatistics::class]);

    // today: 20.03.
    Carbon::setTestNow(Carbon::createFromFormat('d.m.Y', '20.03.2023'));

    $statistics
        ->for($user)
        ->forCountry('de')
        ->forSource('source 1')
        ->hit(3);

    $statistics
        ->for($user)
        ->forCountry('gb')
        ->forSource('source 1')
        ->hit(3);

    // Keep for 14 days
    $keep_daily_statistics_for_days = $statistics->getKeepDailyStatisticsForDays();
    expect($keep_daily_statistics_for_days)->toBe(14);

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
    $statistics = new OrderStatistics();
    $res = $statistics
        ->for($user)
        ->forCountry('de')
        ->forSource('source 1')
        ->get();

    expect($res)->toBe(3.0);

    // Total for all countries should still be 6
    $statistics = new OrderStatistics();
    $res = $statistics
        ->for($user)
        ->forSource('source 1')
        ->get();

    expect($res)->toBe(6.0);
});

it('does not delete daily stats before it should', function () {
    $user = User::first();
    $statistics = new OrderStatistics();
    $model = app(AdvancedStatistics::class)->getModelClass();

    // today: 20.03.
    Carbon::setTestNow(Carbon::createFromFormat('d.m.Y', '20.03.2023'));

    $statistics
        ->for($user)
        ->forCountry('de')
        ->forSource('source 1')
        ->hit(3);

    // Keep for 14 days
    // new now = one day before threshold => 02.04.
    Carbon::setTestNow(now()->addDays($statistics->getKeepDailyStatisticsForDays() - 1));

    // Should have 1 db entry with 'd' timeframe
    expect($model::query()->timeframe('d')->count())->toBe(1);

    $delete_older_than = now()->subDays($statistics->getKeepDailyStatisticsForDays() - 1)->startOfDay();
    expect($delete_older_than->format('d.m.Y'))->toBe('20.03.2023');

    // Do accumulation
    $model::calculateMonthlyStatistics();

    // Should still have 1 daily entry as it is one day before it should be deleted
    expect($model::query()->timeframe('m')->count())->toBe(0);
    expect($model::query()->timeframe('d')->count())->toBe(1);

    // Total should still be 3

    $res = $statistics
        ->for($user)
        ->forCountry('de')
        ->forSource('source 1')
        ->get();

    expect($res)->toBe(3.0);
});
