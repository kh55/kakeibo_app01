<?php

namespace App\Services;

use App\Models\CashflowEntry;
use App\Models\InstallmentPlan;
use App\Models\RecurringRule;
use App\Models\User;
use Carbon\Carbon;

class CashflowService
{
    /**
     * Calculate cashflow balance for a date range.
     */
    public function calculateBalance(User $user, Carbon $startDate, Carbon $endDate, float $initialBalance = 0): array
    {
        $entries = CashflowEntry::where('user_id', $user->id)
            ->whereBetween('date', [$startDate, $endDate])
            ->orderBy('date')
            ->get();

        $balance = $initialBalance;
        $result = [];

        foreach ($entries as $entry) {
            $balance = $balance + $entry->net_amount;
            $result[] = [
                'date' => $entry->date,
                'name' => $entry->name,
                'income' => $entry->income_amount,
                'expense' => $entry->expense_amount,
                'balance' => $balance,
            ];
        }

        return $result;
    }

    /**
     * Sync recurring rules and installment plans to cashflow entries.
     */
    public function syncFromRecurring(User $user, Carbon $startDate, Carbon $endDate): int
    {
        $synced = 0;
        $current = $startDate->copy();

        while ($current->lte($endDate)) {
            // Sync recurring rules
            $rules = RecurringRule::where('user_id', $user->id)
                ->where('enabled', true)
                ->where('day_of_month', '<=', $current->daysInMonth)
                ->get();

            foreach ($rules as $rule) {
                $day = min($rule->day_of_month, $current->daysInMonth);
                $entryDate = $current->copy()->day($day);

                if ($entryDate->between($startDate, $endDate)) {
                    $exists = CashflowEntry::where('user_id', $user->id)
                        ->where('date', $entryDate)
                        ->where('name', $rule->name)
                        ->exists();

                    if (!$exists) {
                        CashflowEntry::create([
                            'user_id' => $user->id,
                            'date' => $entryDate,
                            'name' => $rule->name,
                            'expense_amount' => $rule->amount,
                            'income_amount' => 0,
                        ]);
                        $synced = $synced + 1;
                    }
                }
            }

            // Sync installment plans
            $plans = InstallmentPlan::where('user_id', $user->id)
                ->where('enabled', true)
                ->where('remaining_times', '>', 0)
                ->get();

            foreach ($plans as $plan) {
                $day = min($plan->pay_day, $current->daysInMonth);
                $entryDate = $current->copy()->day($day);

                if ($entryDate->between($startDate, $endDate) && $entryDate->gte($plan->start_date)) {
                    $exists = CashflowEntry::where('user_id', $user->id)
                        ->where('date', $entryDate)
                        ->where('name', $plan->name)
                        ->exists();

                    if (!$exists) {
                        CashflowEntry::create([
                            'user_id' => $user->id,
                            'date' => $entryDate,
                            'name' => $plan->name,
                            'expense_amount' => $plan->amount,
                            'income_amount' => 0,
                        ]);
                        $synced = $synced + 1;
                    }
                }
            }

            $current->addMonth();
        }

        return $synced;
    }
}

