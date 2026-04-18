<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\CashflowEntry>
 */
class CashflowEntryFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'date' => fake()->dateTimeBetween('-1 year', 'now')->format('Y-m-d'),
            'name' => fake()->randomElement(['家賃', 'カード', '給料', 'ローン']),
            'expense_amount' => fake()->randomFloat(2, 0, 200000),
            'income_amount' => 0,
            'memo' => null,
        ];
    }
}
