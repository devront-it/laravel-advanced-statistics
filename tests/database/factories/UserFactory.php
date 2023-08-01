<?php

namespace Devront\AdvancedStatistics\Tests\Database\Factories;

use Devront\AdvancedStatistics\Tests\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class UserFactory extends Factory
{
    protected $model = User::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->name(),
            'email' => $this->faker->unique()->safeEmail()
        ];
    }
}
