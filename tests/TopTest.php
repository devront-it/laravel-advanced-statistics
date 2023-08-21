<?php

use Devront\AdvancedStatistics\Tests\Attributes\ItemStatistics;
use Devront\AdvancedStatistics\Tests\Models\User;


it('can show the top items of a statistic', function () {
    $user = User::first();
    $statistics_write = new ItemStatistics();
    $statistics_write
        ->for($user)
        ->forCountry('de')
        ->forSku('test-1')
        ->hit(3);

    $statistics_write
        ->for($user)
        ->forCountry('fr')
        ->forSku('test-2')
        ->hit(5);

    $statistics_write
        ->for($user)
        ->forCountry('fr')
        ->forSku('test-3')
        ->hit(2);

    $statistics = new ItemStatistics();
    $top3 = $statistics
        ->for($user)
        ->top('sku', 3);

    expect($top3->first()->sku)->toBe("test-2");
    expect($top3->skip(1)->first()->sku)->toBe("test-1");
    expect($top3->skip(2)->first()->sku)->toBe("test-3");


    $statistics = new ItemStatistics();
    $top2_france = $statistics
        ->for($user)
        ->forCountry('fr')
        ->top('sku', 2);

    expect($top2_france->first()->sku)->toBe("test-2");
    expect($top2_france->skip(1)->first()->sku)->toBe("test-3");

    $statistics_write
        ->for($user)
        ->forCountry('fr')
        ->forSku('test-1')
        ->hit(5);

    $statistics = new ItemStatistics();
    $top2_item1 = $statistics
        ->for($user)
        ->top(['sku', 'country'], 3);

    expect($top2_item1->first()->sku)->toBe("test-1");
    expect($top2_item1->first()->country)->toBe("fr");

    expect($top2_item1->skip(1)->first()->sku)->toBe("test-2");
    expect($top2_item1->skip(1)->first()->country)->toBe("fr");

    expect($top2_item1->skip(2)->first()->sku)->toBe("test-1");
    expect($top2_item1->skip(2)->first()->country)->toBe("de");
});
