<?php

namespace App\Services;

use App\Models\Budget;
use App\Models\Transaction;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class DashboardService
{
    /**
     * Get monthly summary for the user.
     */
    public function getMonthlySummary(User $user, ?int $year = null, ?int $month = null): array
    {
        $year = $year ?? Carbon::now()->year;
        $month = $month ?? Carbon::now()->month;

        $income = Transaction::where('user_id', $user->id)
            ->forMonth($year, $month)
            ->income()
            ->sum('amount');

        $expense = Transaction::where('user_id', $user->id)
            ->forMonth($year, $month)
            ->expense()
            ->sum('amount');

        $balance = $income - $expense;

        // Calculate carryover balance
        $previousMonth = Carbon::create($year, $month, 1)->subMonth();
        $previousBalance = $this->getCarryoverBalance($user, $previousMonth->year, $previousMonth->month);
        $carryoverBalance = $previousBalance + $balance;

        return [
            'year' => $year,
            'month' => $month,
            'income' => $income,
            'expense' => $expense,
            'balance' => $balance,
            'carryover_balance' => $carryoverBalance,
        ];
    }

    /**
     * Get carryover balance for a specific month.
     */
    public function getCarryoverBalance(User $user, int $year, int $month): float
    {
        // This should be calculated from initial balance + all previous months' balances
        // For now, simplified calculation
        $initialBalance = 0; // Should be stored in settings
        $previousTransactions = Transaction::where('user_id', $user->id)
            ->where(function ($query) use ($year, $month) {
                $query->whereYear('date', '<', $year)
                    ->orWhere(function ($q) use ($year, $month) {
                        $q->whereYear('date', $year)
                            ->whereMonth('date', '<', $month);
                    });
            })
            ->get();

        $totalIncome = $previousTransactions->where('type', 'income')->sum('amount');
        $totalExpense = $previousTransactions->where('type', 'expense')->sum('amount');

        return $initialBalance + $totalIncome - $totalExpense;
    }

    /**
     * Get category-wise expense summary.
     */
    public function getCategoryExpenseSummary(User $user, int $year, int $month, int $limit = 10): array
    {
        return Transaction::where('user_id', $user->id)
            ->forMonth($year, $month)
            ->expense()
            ->select('category_id', DB::raw('SUM(amount) as total'))
            ->groupBy('category_id')
            ->with('category')
            ->orderByDesc('total')
            ->limit($limit)
            ->get()
            ->map(function ($item) {
                return [
                    'category_name' => $item->category?->name ?? '未分類',
                    'total' => $item->total,
                ];
            })
            ->toArray();
    }

    /**
     * Get budget vs actual comparison.
     */
    public function getBudgetComparison(User $user, int $year, int $month): array
    {
        $budgets = Budget::where('user_id', $user->id)
            ->where('year', $year)
            ->where('month', $month)
            ->with('category')
            ->get();

        $actuals = Transaction::where('user_id', $user->id)
            ->forMonth($year, $month)
            ->expense()
            ->select('category_id', DB::raw('SUM(amount) as total'))
            ->groupBy('category_id')
            ->pluck('total', 'category_id');

        return $budgets->map(function ($budget) use ($actuals) {
            $actual = $actuals->get($budget->category_id, 0);

            return [
                'category_name' => $budget->category->name,
                'budget' => $budget->amount,
                'actual' => $actual,
                'difference' => $budget->amount - $actual,
            ];
        })->toArray();
    }
}

