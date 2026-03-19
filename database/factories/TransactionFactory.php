<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Transaction>
 */
class TransactionFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => \App\Models\User::factory(),
            'date' => fake()->dateTimeBetween('-1 year', 'now')->format('Y-m-d'),
            'type' => fake()->randomElement(['income', 'expense']),
            'account_id' => null,
            'category_id' => null,
            'name' => fake()->words(3, true),
            'amount' => fake()->numberBetween(100, 100000),
            'is_recurring' => false,
            'memo' => null,
            'tags' => null,
        ];
    }
}
