<?php

namespace App\Services;

use App\Models\InstallmentPlan;
use App\Models\Transaction;
use App\Models\User;

class InstallmentAutoPaymentService
{
    public function recordForMonth(User $user): int
    {
        $plans = InstallmentPlan::where('user_id', $user->id)
            ->where('enabled', true)
            ->where('remaining_times', '>', 0)
            ->get();

        $recorded = 0;

        foreach ($plans as $plan) {
            $alreadyRecorded = Transaction::where('user_id', $user->id)
                ->where('installment_plan_id', $plan->id)
                ->whereYear('date', now()->year)
                ->whereMonth('date', now()->month)
                ->exists();

            if ($alreadyRecorded) {
                continue;
            }

            $payDay = min($plan->pay_day, now()->daysInMonth);
            $payDate = now()->setDay($payDay)->format('Y-m-d');

            Transaction::create([
                'user_id' => $user->id,
                'date' => $payDate,
                'type' => 'expense',
                'account_id' => $plan->account_id,
                'category_id' => $plan->category_id,
                'name' => $plan->name,
                'amount' => $plan->amount,
                'is_recurring' => false,
                'installment_plan_id' => $plan->id,
            ]);

            $plan->decrement('remaining_times');

            if ($plan->remaining_times === 0) {
                $plan->update(['enabled' => false]);
            }

            $recorded++;
        }

        return $recorded;
    }
}
