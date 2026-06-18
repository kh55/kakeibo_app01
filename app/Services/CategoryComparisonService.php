<?php

namespace App\Services;

use App\Models\Transaction;
use App\Models\User;
use Carbon\Carbon;

class CategoryComparisonService
{
    /**
     * 指定分類の日別支出を、基準月と前月で比較する。
     *
     * @return array{labels: list<int>, current: list<float|null>, previous: list<float|null>, currentTotal: float, previousTotal: float, currentLabel: string, previousLabel: string}
     */
    public function getDailyComparison(User $user, int $categoryId, Carbon $baseMonth): array
    {
        $currentStart = $baseMonth->copy()->startOfMonth();
        $currentEnd = $currentStart->copy()->endOfMonth();
        $previousStart = $currentStart->copy()->subMonth();
        $previousEnd = $previousStart->copy()->endOfMonth();

        $currentDays = $currentStart->daysInMonth;
        $previousDays = $previousStart->daysInMonth;
        $maxDays = max($currentDays, $previousDays);

        $currentTotals = $this->dailyTotals($user, $categoryId, $currentStart, $currentEnd);
        $previousTotals = $this->dailyTotals($user, $categoryId, $previousStart, $previousEnd);

        $now = Carbon::now();
        $isCurrentMonth = $currentStart->isSameMonth($now);
        $today = $now->day;

        $labels = [];
        $current = [];
        $previous = [];
        for ($d = 1; $d <= $maxDays; $d++) {
            $labels[] = $d;

            if ($d > $currentDays || ($isCurrentMonth && $d > $today)) {
                $current[] = null;
            } else {
                $current[] = $currentTotals[$d] ?? 0.0;
            }

            $previous[] = $d > $previousDays ? null : ($previousTotals[$d] ?? 0.0);
        }

        return [
            'labels' => $labels,
            'current' => $current,
            'previous' => $previous,
            'currentTotal' => (float) array_sum($current),
            'previousTotal' => (float) array_sum($previous),
            'currentLabel' => $currentStart->format('Y-m'),
            'previousLabel' => $previousStart->format('Y-m'),
        ];
    }

    /**
     * 指定分類の月別支出を、基準年と前年で比較する。
     *
     * @return array{labels: list<string>, current: list<float|null>, previous: list<float>, currentTotal: float, previousTotal: float, currentLabel: string, previousLabel: string}
     */
    public function getMonthlyComparison(User $user, int $categoryId, int $baseYear): array
    {
        $currentTotals = $this->monthlyTotals($user, $categoryId, $baseYear);
        $previousTotals = $this->monthlyTotals($user, $categoryId, $baseYear - 1);

        $now = Carbon::now();
        $isCurrentYear = $baseYear === $now->year;
        $currentMonth = $now->month;

        $labels = [];
        $current = [];
        $previous = [];
        for ($m = 1; $m <= 12; $m++) {
            $labels[] = $m.'月';

            if ($isCurrentYear && $m > $currentMonth) {
                $current[] = null;
            } else {
                $current[] = $currentTotals[$m] ?? 0.0;
            }

            $previous[] = $previousTotals[$m] ?? 0.0;
        }

        return [
            'labels' => $labels,
            'current' => $current,
            'previous' => $previous,
            'currentTotal' => (float) array_sum($current),
            'previousTotal' => (float) array_sum($previous),
            'currentLabel' => (string) $baseYear,
            'previousLabel' => (string) ($baseYear - 1),
        ];
    }

    /**
     * 指定期間・分類の支出を日（1〜31）ごとに合計する。
     *
     * @return array<int, float>
     */
    private function dailyTotals(User $user, int $categoryId, Carbon $start, Carbon $end): array
    {
        $transactions = Transaction::where('user_id', $user->id)
            ->where('category_id', $categoryId)
            ->expense()
            ->whereBetween('date', [$start->toDateString(), $end->toDateString()])
            ->get(['date', 'amount']);

        $totals = [];
        foreach ($transactions as $transaction) {
            $day = $transaction->date->day;
            $totals[$day] = ($totals[$day] ?? 0.0) + (float) $transaction->amount;
        }

        return $totals;
    }

    /**
     * 指定年・分類の支出を月（1〜12）ごとに合計する。
     *
     * @return array<int, float>
     */
    private function monthlyTotals(User $user, int $categoryId, int $year): array
    {
        $transactions = Transaction::where('user_id', $user->id)
            ->where('category_id', $categoryId)
            ->expense()
            ->whereYear('date', $year)
            ->get(['date', 'amount']);

        $totals = [];
        foreach ($transactions as $transaction) {
            $month = $transaction->date->month;
            $totals[$month] = ($totals[$month] ?? 0.0) + (float) $transaction->amount;
        }

        return $totals;
    }
}
