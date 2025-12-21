<?php

namespace App\Http\Controllers;

use App\Models\CashflowEntry;
use App\Services\CashflowService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CashflowController extends Controller
{
    public function __construct(
        private CashflowService $cashflowService
    ) {
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $user = Auth::user();
        $startDate = $request->get('start_date', Carbon::now()->startOfMonth()->format('Y-m-d'));
        $endDate = $request->get('end_date', Carbon::now()->addMonths(3)->endOfMonth()->format('Y-m-d'));

        $entries = CashflowEntry::where('user_id', $user->id)
            ->whereBetween('date', [$startDate, $endDate])
            ->orderBy('date')
            ->get();

        $balance = $this->cashflowService->calculateBalance(
            $user,
            Carbon::parse($startDate),
            Carbon::parse($endDate)
        );

        return view('cashflow.index', compact('entries', 'balance', 'startDate', 'endDate'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('cashflow.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'date' => 'required|date',
            'name' => 'required|string|max:255',
            'expense_amount' => 'nullable|numeric|min:0',
            'income_amount' => 'nullable|numeric|min:0',
            'memo' => 'nullable|string',
        ]);

        $validated['user_id'] = Auth::id();
        $validated['expense_amount'] = $validated['expense_amount'] ?? 0;
        $validated['income_amount'] = $validated['income_amount'] ?? 0;

        CashflowEntry::create($validated);

        return redirect()->route('cashflow.index')
            ->with('success', '予定を登録しました。');
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(CashflowEntry $cashflowEntry)
    {
        $this->authorize('update', $cashflowEntry);

        return view('cashflow.edit', compact('cashflowEntry'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, CashflowEntry $cashflowEntry)
    {
        $this->authorize('update', $cashflowEntry);

        $validated = $request->validate([
            'date' => 'required|date',
            'name' => 'required|string|max:255',
            'expense_amount' => 'nullable|numeric|min:0',
            'income_amount' => 'nullable|numeric|min:0',
            'memo' => 'nullable|string',
        ]);

        $validated['expense_amount'] = $validated['expense_amount'] ?? 0;
        $validated['income_amount'] = $validated['income_amount'] ?? 0;

        $cashflowEntry->update($validated);

        return redirect()->route('cashflow.index')
            ->with('success', '予定を更新しました。');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(CashflowEntry $cashflowEntry)
    {
        $this->authorize('delete', $cashflowEntry);
        $cashflowEntry->delete();

        return redirect()->route('cashflow.index')
            ->with('success', '予定を削除しました。');
    }

    /**
     * Sync from recurring rules and installment plans.
     */
    public function sync(Request $request)
    {
        $user = Auth::user();
        $startDate = Carbon::parse($request->get('start_date', Carbon::now()->startOfMonth()->format('Y-m-d')));
        $endDate = Carbon::parse($request->get('end_date', Carbon::now()->addMonths(3)->endOfMonth()->format('Y-m-d')));

        $synced = $this->cashflowService->syncFromRecurring($user, $startDate, $endDate);

        return redirect()->route('cashflow.index')
            ->with('success', "{$synced}件の予定を同期しました。");
    }
}
