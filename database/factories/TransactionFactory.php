<?php

namespace Database\Factories;

use App\Models\Client;
use App\Models\Transaction;
use Illuminate\Database\Eloquent\Factories\Factory;

class TransactionFactory extends Factory
{
    protected $model = Transaction::class;

    public function definition(): array
    {
        return [
            'client_id' => Client::factory(),
            'bank' => fake()->randomElement(['paytech', 'acme']),
            'reference' => fake()->unique()->numerify('202506159######'),
            'amount' => fake()->randomFloat(2, 10, 5000),
            'date' => fake()->date(),
            'metadata' => null,
        ];
    }
}
