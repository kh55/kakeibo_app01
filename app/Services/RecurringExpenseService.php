<?php

namespace App\Services;

use App\Models\RecurringRule;
use App\Models\Transaction;
use App\Models\User;
use Carbon\Carbon;

class RecurringExpenseService
{
    /**
     * Generate transactions from recurring rules for a specific month.
     */
    public function generateTransactionsForMonth(User $user, int $year, int $month): int
    {
        $generated = 0;
        $date = Carbon::create($year, $month, 1);
        $lastDay = $date->copy()->endOfMonth()->day;

        $rules = RecurringRule::where('user_id', $user->id)
            ->where('enabled', true)
            ->get();

        foreach ($rules as $rule) {
            $day = min($rule->day_of_month, $lastDay);
            $transactionDate = Carbon::create($year, $month, $day);

            // Check if transaction already exists
            $exists = Transaction::where('user_id', $user->id)
                ->where('date', $transactionDate->format('Y-m-d'))
                ->where('account_id', $rule->account_id)
                ->where('name', $rule->name)
                ->where('amount', $rule->amount)
                ->where('is_recurring', true)
                ->exists();

            if (!$exists) {
                Transaction::create([
                    'user_id' => $user->id,
                    'date' => $transactionDate,
                    'type' => 'expense',
                    'account_id' => $rule->account_id,
                    'category_id' => $rule->category_id,
                    'name' => $rule->name,
                    'amount' => $rule->amount,
                    'is_recurring' => true,
                ]);
                $generated++;
            }
        }

        return $generated;
    }
}

