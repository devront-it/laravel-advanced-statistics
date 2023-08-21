# Laravel Advanced Statistics (no UI)

[![Latest Version on Packagist](https://img.shields.io/packagist/v/devront-it/laravel-advanced-statistics.svg?style=flat-square)](https://packagist.org/packages/devront-it/laravel-advanced-statistics)
[![Total Downloads](https://img.shields.io/packagist/dt/devront-it/laravel-advanced-statistics.svg?style=flat-square)](https://packagist.org/packages/devront-it/laravel-advanced-statistics)
![Tests](https://github.com/devront-it/laravel-advanced-statistics/actions/workflows/test.yml/badge.svg)

I hate to think about statistics everytime I start a new project. That's why - in my applications - the Dashboard is the
last thing that gets filled. What I needed was a simple API where I can define, increment, count and clean up statistics
for all kind of purposes. This is the result.

Key features:

* Define your statistics as a simple php class
* Define how long you would like to keep daily and monthly statistics
* Cleanup: Daily statistics are automatically accumulated into monthly statistics
* Simply query your statistics with magic methods (ide helper generator included)
* Calculate averages

How it works

This package does not just rely on counting models. An example: I don't want to know how many orders I have in my
database. You really wouldn't need a package for that. What I need to know is:

* How many orders did I have from country x?
* How long did my fulfillment provider need to fulfill my orders in average?
* Combination of both: How many hours did my German fulfillment provider need in average to fulfill orders during peek
  time in November compared to my fulfillment provider in Spain?
* This list can go on...

Some of those queries can be really expensive. That's why this package stores everything in its own database table. It
separates daily from monthly statistics and merges both automatically after x days, which can be configured for every
statistic. It also cleans up stats after a pre-defined timeframe.

Maybe one day I'll make some Livewire components that utilize this package to provide a UI.

## Installation

Install the package via composer:

```bash
composer require devront-it/laravel-advanced-statistics
```

Publish and run the migration with:

```bash
php artisan vendor:publish --tag="advanced-statistics-migrations"
php artisan migrate
```

The table name will be 'advanced_statistics' by default. If it conflicts with other tables, just add this to your
ApplicationServiceProvider:

```php
statistics()->withTablePrefix('custom_prefix_'); // Will result in 'custom_prefix_statistics' table
// or app(Devront\AdvancedStatistics\AdvancedStatistics::class)->withTablePrefix(...)
```

No config file needed.

### Scheduler

This package comes with a Job that should run daily at 00:00.

```php
// Inside your App\Console\Kernel.php

use \Devront\AdvancedStatistics\Jobs\CalculateStatisticsJob;

$schedule->job(CalculateStatisticsJob::class)
            ->dailyAt('0:0')
            ->onOneServer();
```

## Usage

First you need to define a class for your statistics

```php
use Devront\AdvancedStatistics\Attributes\AdvancedStatisticsAttribute;
use Devront\AdvancedStatistics\Statistics;

#[AdvancedStatisticsAttribute]
class OrderStatistics extends Statistics 
{
}
```

For simple statistics where you just want to count up a value this would be enough. But you can also define some
params/metadata.

```php
use Devront\AdvancedStatistics\Attributes\AdvancedStatisticsAttribute;
use Devront\AdvancedStatistics\Attributes\Param;
use Devront\AdvancedStatistics\Statistics;

#[AdvancedStatisticsAttribute]
class OrderStatistics extends Statistics 
{
    #[Param]
    protected string $country_code;
    
    #[Param]
    protected string $payment_method;
}
```

You can pass some params to the AdvancedStatisticsAttribute:

* **type**: The unique type fo your statistic. Defaults to the name of your class, in this
  case ``<namespace>\OrderStatistics``
* **keepDailyStatisticsForDays**: How many days you'd like to keep daily statistics before they are accumulated and
  stored in a monthly statistic. Defaults to 90 days.
* **keepMonthlyStatisticsForMonths**: How many months you'd like to keep monthly statistics before they are deleted.
  Defaults to 24 months.

```php
use Devront\AdvancedStatistics\Attributes\AdvancedStatisticsAttribute;
use Devront\AdvancedStatistics\Attributes\Param;
use Devront\AdvancedStatistics\Statistics;

#[AdvancedStatisticsAttribute(
    type: 'orders',
    keepDailyStatisticsForDays: 90,
    keepMonthlyStatisticsForMonths: 24
)]
class OrderStatistics extends Statistics 
{
    #[Param]
    protected string $country_code;
    
    #[Param]
    protected string $payment_method;
}
```

### Update and retrieve values: ``->get()`` and ``->hit()``

Now you can start counting and retrieving statistics. When writing your query, you can end it with either ``hit()`` for
incrementing the value or ``get()`` to retrieve a value.

Available methods for chaining your query are:

| method                                                                           | arguments                                | required | for get() or hit()? | description                                                     |
|----------------------------------------------------------------------------------|------------------------------------------|----------|---------------------|-----------------------------------------------------------------|
| ``for($owner)``                                                                  | owner: Model or class                    | false    | both                | Defines the owner of the statistic you want to get or hit.      |
| ``from($date)``                                                                  | date: Value that can be parsed by Carbon | false    | ``get()``           | Define the from-date for your query.                            |
| ``to($date)``                                                                    | date: Value that can be parsed by Carbon | false    | ``get()``           | Define the to-date for your query.                              |
| ``forCountryCode($value)``<br/> ``forPaymentMethod($value)`` <br/> and so on ... | value: Your metadata value               | false    | both                | All your custom params are accessible with this magic methods.  |

**Note on the behavior of custom params:**

```php
// hit() without forCountryCode() will result in incrementing the statistics for orders with country_code=null
(new OrderStatistics)
    ->for($user)
    ->hit();
```

```php
// get() without forCountryCode() will result in counting all orders together, no matter what value country_code has
(new OrderStatistics)
    ->for($user)
    ->get();
```

```php
// If you want to count all orders where country_code is null, you need to explicitly write it down:
(new OrderStatistics)
    ->for($user)
    ->forCountryCode(null)
    ->get();
```

### Averages

Statistic Class config:

```php
use Devront\AdvancedStatistics\Attributes\Avg;

#[AdvancedStatisticsAttribute]
class OrderStatistics extends Statistics 
{
    // ...other params
    
    /**
    * How many hours passed between ordered and shipped
    */
    #[Avg]
    protected float $time_to_fulfill;
}
```

**Usage:**

```php
(new OrderStatistics)
    ->for($user)
    ->timeToFulfill(2.5)
    // ... maybe other params
    ->hit();

(new OrderStatistics)
    ->for($user)
    ->timeToFulfill(4)
    // ... maybe other params
    ->hit();

// Get the average, returns 3.25
$average = (new OrderStatistics)
                ->for($user)
                ->from(now()->startOfYear())
                // ... maybe other params
                ->getAverageTimeToFulfill(); // Takes decimal places as argument, default = 2
                // ->getAverageTimeToFulfill(1); would return the value rounded to one place: 3.3

```

*Note:* If your Statistic Class contains a ``#[Avg]`` value, you must always provide it before ``->hit()`` the stats.

#### Passing ``null`` to an average param

The average param (timeToFulfill in this example) can also be ``null``. If you pass null, then the average will remain
unchanged. This can be useful if you want to count up the statistics but for some reason you have a very unrealistic
value and don't want it to mess up your average stats.

For example, you could have a very old test order that you forgot about. One day you decide to ship it just to get rid
of it. Now the timeToFulfill will be very big and could destroy your average stats. So you could define a threshold and
pass null as timeToFulfill if its exceeded.

### top() Method

The ``top()`` method offers a flexible and powerful way to retrieve the top records based on specified fields from the
payload (your custom params). This functionality allows you to group and aggregate data either by a single field or a
combination of multiple fields.

**Usage:**

Top 5 products grouped by a single field (sku):

```php
    // Get the top 5 best-selling products 
    $top_selling_5_products = (new YourItemStats)
                                    ->for($user)
                                    // ... your other filters like from() and to()
                                    ->top('sku', 5);

    $top_selling_5_products->first()->sku;
    $top_selling_5_products->first()->total_value;
```

Top 10 results grouped by multiple fields:

```php
    $top_results = (new YourCustomStats)
                                    ->for($user)
                                    // ... your other filters like from() and to()
                                    ->top(['param1', 'param2'], 10);

    $top_results->first()->param1;
    $top_results->first()->param2;
    $top_results->first()->total_value;
```

Return value:

The method returns a collection of the top records, sorted in descending order based on their aggregated values over a
specified period. Each entry in the result will include the fields defined in the ``fields`` parameter, alongside an
aggregated ``total_value`` representing the computed metric for that group.

### IDE Autocompletion of magic methods

This package comes with a command to generate a _ide_helper_statistics.php file for better autocompletion when chaining
magic methods like forCountryCode() in the example above:

```bash
php artisan ide-helper:advanced-statistics
```

## Configuration

### Using uuids

If you are using uuids, make sure to put this inside the boot method of your AppServiceProvider BEFORE running the
migration:

```php
statistics()->useUuids();
```

This will set the primary key and the owner_id of the morph relation to be a uuid.

### Extending the default Statistic model

If you want to extend the default Statistic model, specify your model inside your AppServiceProvider's boot method:

```php
statistics()->useModel(YourCustomModel::class);
```

```php
class YourCustomModel extends \Devront\AdvancedStatistics\Models\Statistic {
    ...
}
```

## Changelog

This is the initial release.

## Contributing

Contribution is welcome.

## Security Vulnerabilities

Please let me know if you find security vulnerabilities.

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
