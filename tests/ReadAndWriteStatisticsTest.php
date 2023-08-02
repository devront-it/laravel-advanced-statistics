<?php

use Devront\AdvancedStatistics\Tests\Attributes\OrderStatistics;
use Devront\AdvancedStatistics\Tests\Models\User;


it('sorts the params of the statistics class alphanumeric', function () {
    $statistics = new OrderStatistics();
    $params = $statistics->getParams();
    expect($params[0])->toBe('country');
    expect($params[1])->toBe('source');
});

it('can hit and get statistics with params', function () {
    $user = User::first();
    $statistics = new OrderStatistics();
    $statistics
        ->for($user)
        ->forCountry('de')
        ->forSource('source 1')
        ->hit(3);

    $statistics
        ->for($user)
        ->forCountry('fr')
        ->forSource('source 1')
        ->hit(3);

    $statistics
        ->for($user)
        ->forCountry('es')
        ->forSource('source 2')
        ->hit(3);

    $statistics = new OrderStatistics();
    $total_orders_for_germany_and_france = $statistics
        ->for($user)
        ->forCountry(['de', 'fr'])
        ->get();

    $statistics = new OrderStatistics();
    $total_orders_for_user_for_source_in_germany = $statistics
        ->for($user)
        ->forSource('source 1')
        ->forCountry('de')
        ->get();

    $statistics = new OrderStatistics();
    $total_orders_for_user_for_source_1_in_all_countries = $statistics
        ->for($user)
        ->forSource('source 1')
        ->get();

    $statistics = new OrderStatistics();
    $total_orders = $statistics
        ->get();

    expect($total_orders_for_germany_and_france)->toBe(6.0);
    expect($total_orders_for_user_for_source_1_in_all_countries)->toBe(6.0);
    expect($total_orders)->toBe(9.0);
    expect($total_orders_for_user_for_source_in_germany)->toBe(3.0);
});


it('can hit and get statistics for multiple owners', function () {
    $user1 = User::find(1);
    $user2 = User::find(2);
    $user3 = User::find(3);
    $statistics = new OrderStatistics();
    $statistics
        ->for($user1)
        ->forCountry('de')
        ->forSource('source 1')
        ->hit(3);

    $statistics
        ->for($user2)
        ->forCountry('fr')
        ->forSource('source 1')
        ->hit(3);

    $statistics
        ->for($user3)
        ->forCountry('fr')
        ->forSource('source 1')
        ->hit(3);

    $statistics = new OrderStatistics();
    $total_orders_for_first_and_third_user = $statistics
        ->for(User::class, [1, 3])
        ->get();
    expect($total_orders_for_first_and_third_user)->toBe(6.0);

    $statistics = new OrderStatistics();
    $total_orders_for_first_and_third_user = $statistics
        ->for(User::class)
        ->get();
    expect($total_orders_for_first_and_third_user)->toBe(9.0);

    $statistics = new OrderStatistics();
    $total_orders_for_first_and_third_user = $statistics
        ->for(User::class, [])
        ->get();
    expect($total_orders_for_first_and_third_user)->toBe(0);
});
