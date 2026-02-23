<?php

namespace App\Http\Controllers;

use App\Services\DashboardService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __construct(
        private DashboardService $dashboardService
    ) {}

    /**
     * Display the dashboard.
     */
    public function index(Request $request)
    {
        $year = $request->get('year', Carbon::now()->year);
        $month = $request->get('month', Carbon::now()->month);

        $user = Auth::user();
        $summary = $this->dashboardService->getMonthlySummary($user, $year, $month);
        $categoryExpenses = $this->dashboardService->getCategoryExpenseSummary($user, $year, $month);
        $budgetComparison = $this->dashboardService->getBudgetComparison($user, $year, $month);

        // Get upcoming payments
        $upcomingPayments = $this->getUpcomingPayments($user);

        $isLocal = app()->environment('local');
        $localTestDataYearMonth = null;

        if ($isLocal) {
            $path = storage_path('app/local-test-data.json');
            if (is_file($path)) {
                $json = json_decode((string) file_get_contents($path), true);
                if (is_array($json) && isset($json['yearMonth']) && is_string($json['yearMonth'])) {
                    $localTestDataYearMonth = $json['yearMonth'];
                }
            }
        }

        return view('dashboard.index', compact(
            'summary',
            'categoryExpenses',
            'budgetComparison',
            'upcomingPayments',
            'year',
            'month',
            'isLocal',
            'localTestDataYearMonth',
        ));
    }

    /**
     * Display annual summary (monthly income, expense, balance, carryover).
     */
    public function annualSummary(Request $request): View
    {
        $year = (int) $request->get('year', Carbon::now()->year);
        $user = Auth::user();
        $rows = $this->dashboardService->getAnnualSummary($user, $year);

        return view('dashboard.annual', compact('year', 'rows'));
    }

    /**
     * Get upcoming payments and reminders.
     */
    private function getUpcomingPayments($user): array
    {
        $now = Carbon::now();
        $endOfMonth = $now->copy()->endOfMonth();

        // Recurring rules for this month
        $recurringRules = \App\Models\RecurringRule::where('user_id', $user->id)
            ->where('enabled', true)
            ->where('day_of_month', '>=', $now->day)
            ->where('day_of_month', '<=', $endOfMonth->day)
            ->get();

        // Installment plans
        $installmentPlans = \App\Models\InstallmentPlan::where('user_id', $user->id)
            ->where('enabled', true)
            ->where('remaining_times', '>', 0)
            ->get()
            ->filter(function ($plan) use ($now, $endOfMonth) {
                $payDate = Carbon::create($now->year, $now->month, min($plan->pay_day, $now->daysInMonth));

                return $payDate->between($now, $endOfMonth) && $payDate->gte($plan->start_date);
            });

        return [
            'recurring' => $recurringRules,
            'installments' => $installmentPlans,
        ];
    }
}
