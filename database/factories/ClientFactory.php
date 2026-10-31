<?php

namespace Database\Factories;

use App\Models\Client;
use Illuminate\Database\Eloquent\Factories\Factory;

class ClientFactory extends Factory
{
    protected $model = Client::class;

    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'account_number' => 'SA'.fake()->numerify('######################'),
            'email' => fake()->unique()->safeEmail(),
            'balance' => 0.00,
        ];
    }
}
