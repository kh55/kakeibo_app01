<?php

namespace App\Http\Controllers;

use App\Models\RecurringRule;
use App\Services\RecurringExpenseService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class RecurringRuleController extends Controller
{
    public function __construct(
        private RecurringExpenseService $recurringExpenseService
    ) {}

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $user = Auth::user();
        $rules = $user->recurringRules()->with(['account', 'category'])->get();

        return view('recurring.index', compact('rules'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $user = Auth::user();
        $accounts = $user->accounts()->where('enabled', true)->orderBy('sort_order')->get();
        $categories = $user->categories()->where('type', 'expense')->orderBy('sort_order')->get();

        return view('recurring.create', compact('accounts', 'categories'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'account_id' => 'required|exists:accounts,id',
            'day_of_month' => 'required|integer|min:1|max:31',
            'name' => 'required|string|max:255',
            'amount' => 'required|numeric|min:0',
            'category_id' => 'nullable|exists:categories,id',
            'enabled' => 'boolean',
        ]);

        $validated['user_id'] = Auth::id();
        RecurringRule::create($validated);

        return redirect()->route('recurring-rules.index')
            ->with('success', '定期支出を登録しました。');
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(RecurringRule $recurringRule)
    {
        $this->authorize('update', $recurringRule);
        $user = Auth::user();
        $accounts = $user->accounts()->where('enabled', true)->orderBy('sort_order')->get();
        $categories = $user->categories()->where('type', 'expense')->orderBy('sort_order')->get();

        return view('recurring.edit', compact('recurringRule', 'accounts', 'categories'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, RecurringRule $recurringRule)
    {
        $this->authorize('update', $recurringRule);

        $validated = $request->validate([
            'account_id' => 'required|exists:accounts,id',
            'day_of_month' => 'required|integer|min:1|max:31',
            'name' => 'required|string|max:255',
            'amount' => 'required|numeric|min:0',
            'category_id' => 'nullable|exists:categories,id',
            'enabled' => 'boolean',
        ]);

        $recurringRule->update($validated);

        return redirect()->route('recurring-rules.index')
            ->with('success', '定期支出を更新しました。');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(RecurringRule $recurringRule)
    {
        $this->authorize('delete', $recurringRule);
        $recurringRule->delete();

        return redirect()->route('recurring-rules.index')
            ->with('success', '定期支出を削除しました。');
    }

    /**
     * Generate transactions for current month.
     */
    public function generate(Request $request)
    {
        $user = Auth::user();
        $year = $request->get('year', Carbon::now()->year);
        $month = $request->get('month', Carbon::now()->month);

        $generated = $this->recurringExpenseService->generateTransactionsForMonth($user, $year, $month);

        return redirect()->route('recurring-rules.index')
            ->with('success', "{$generated}件の取引を生成しました。");
    }
}
