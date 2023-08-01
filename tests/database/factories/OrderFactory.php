<?php

namespace Devront\AdvancedStatistics\Tests\Database\Factories;

use Devront\AdvancedStatistics\Tests\Models\Order;
use Devront\AdvancedStatistics\Tests\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class OrderFactory extends Factory
{
    protected $model = Order::class;

    public function definition(): array
    {
        $users = User::all()->pluck('id')->toArray();
        return [
            'number' => $this->faker->unique()->numberBetween(10000, 99999),
            'country' => $this->faker->randomElement([
                'de',
                'us',
                'at',
                'es',
                'fr',
                'gb'
            ]),
            'source' => $this->faker->randomElement([
                'source 1',
                'source 2',
                'source 3',
            ]),
            'user_id' => $this->faker->randomElement($users)
        ];
    }
}
