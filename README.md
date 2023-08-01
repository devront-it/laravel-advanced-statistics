# Laravel Advanced Statistics (no UI)

[![Latest Version on Packagist](https://img.shields.io/packagist/v/:vendor_slug/:package_slug.svg?style=flat-square)](https://packagist.org/packages/:vendor_slug/:package_slug)
[![GitHub Tests Action Status](https://img.shields.io/github/actions/workflow/status/:vendor_slug/:package_slug/run-tests.yml?branch=main&label=tests&style=flat-square)](https://github.com/:vendor_slug/:package_slug/actions?query=workflow%3Arun-tests+branch%3Amain)
[![GitHub Code Style Action Status](https://img.shields.io/github/actions/workflow/status/:vendor_slug/:package_slug/fix-php-code-style-issues.yml?branch=main&label=code%20style&style=flat-square)](https://github.com/:vendor_slug/:package_slug/actions?query=workflow%3A"Fix+PHP+code+style+issues"+branch%3Amain)
[![Total Downloads](https://img.shields.io/packagist/dt/:vendor_slug/:package_slug.svg?style=flat-square)](https://packagist.org/packages/:vendor_slug/:package_slug)

I hate to think about statistics everytime I start a new project. That's why - in my applications - the Dashboard is the
last thing that gets filled. What I needed was a simple API where I can define, increment, count and clean up statistics
for all kind of purposes. This is the result.

Key features:

* Define your statistics as a simple php class
* Define how long you would like to keep daily and monthly statistics
* Cleanup: Daily statistics are automatically accumulated into monthly statistics
* Simply query your statistics with magic methods (ide helper generator included)

### Show me the code!

```php
// This is how you define your statistics.
// Let's say you want to count orders - but you want niceties, such as:
// How many orders did I ship to Germany?
// What is the most used payment method?
// What is the preferred payment method for orders from Germany?

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
    public string $country_code;
    
    #[Param]
    public string $payment_method;
}


// Now let's count it up:
(new OrderStatistics)
    ->for($user) // The owner model. Can be null of course
    ->forCountryCode('de') // Now simply chain your params
    ->forPaymentMethod('paypal')
    ->hit(1); // Default is 1.

// Let's get how many orders we had this month from Germany:
(new OrderStatistics)
    ->for($user)
    ->forCountryCode('de')
    ->from(now()->startOfMonth())
    ->get();
    
// Let's get how many orders from Spain have been paid with paypal last month
(new OrderStatistics)
    ->for($user)
    ->forCountryCode('es')
    ->forPaymentMethod('paypal')
    ->from(now()->subMonth()->startOfMonth())
    ->to(now()->submonth()->endOfMonth())
    ->get();

```

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
    public string $country_code;
    
    #[Param]
    public string $payment_method;
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
    public string $country_code;
    
    #[Param]
    public string $payment_method;
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

## Configuration

### Using uuids

If you are using uuids, make sure to put this inside the boot method of your AppServiceProvider BEFORE running the migration:

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
