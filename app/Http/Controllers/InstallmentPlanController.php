<?php

namespace App\Http\Controllers;

use App\Models\InstallmentPlan;
use App\Models\Transaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class InstallmentPlanController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $user = Auth::user();
        $plans = $user->installmentPlans()->with(['account', 'category'])->get();

        return view('installments.index', compact('plans'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $user = Auth::user();
        $accounts = $user->accounts()->where('enabled', true)->orderBy('sort_order')->get();
        $categories = $user->categories()->where('type', 'expense')->orderBy('sort_order')->get();

        return view('installments.create', compact('accounts', 'categories'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'start_date' => 'required|date',
            'pay_day' => 'required|integer|min:1|max:31',
            'amount' => 'required|numeric|min:0',
            'times' => 'required|integer|min:1',
            'account_id' => 'nullable|exists:accounts,id',
            'category_id' => 'nullable|exists:categories,id',
            'enabled' => 'boolean',
        ]);

        $validated['user_id'] = Auth::id();
        $validated['remaining_times'] = $validated['times'];
        InstallmentPlan::create($validated);

        return redirect()->route('installment-plans.index')
            ->with('success', '分割払いを登録しました。');
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(InstallmentPlan $installmentPlan)
    {
        $this->authorize('update', $installmentPlan);
        $user = Auth::user();
        $accounts = $user->accounts()->where('enabled', true)->orderBy('sort_order')->get();
        $categories = $user->categories()->where('type', 'expense')->orderBy('sort_order')->get();

        return view('installments.edit', compact('installmentPlan', 'accounts', 'categories'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, InstallmentPlan $installmentPlan)
    {
        $this->authorize('update', $installmentPlan);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'start_date' => 'required|date',
            'pay_day' => 'required|integer|min:1|max:31',
            'amount' => 'required|numeric|min:0',
            'times' => 'required|integer|min:1',
            'remaining_times' => 'required|integer|min:0',
            'account_id' => 'nullable|exists:accounts,id',
            'category_id' => 'nullable|exists:categories,id',
            'enabled' => 'boolean',
        ]);

        $installmentPlan->update($validated);

        // Auto-close if remaining_times is 0
        if ($installmentPlan->remaining_times === 0) {
            $installmentPlan->update(['enabled' => false]);
        }

        return redirect()->route('installment-plans.index')
            ->with('success', '分割払いを更新しました。');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(InstallmentPlan $installmentPlan)
    {
        $this->authorize('delete', $installmentPlan);
        $installmentPlan->delete();

        return redirect()->route('installment-plans.index')
            ->with('success', '分割払いを削除しました。');
    }

    /**
     * Record a payment for the installment plan.
     */
    public function recordPayment(InstallmentPlan $installmentPlan, Request $request)
    {
        $this->authorize('update', $installmentPlan);

        $validated = $request->validate([
            'date' => 'required|date',
        ]);

        if ($installmentPlan->remaining_times > 0) {
            Transaction::create([
                'user_id' => Auth::id(),
                'date' => $validated['date'],
                'type' => 'expense',
                'account_id' => $installmentPlan->account_id,
                'category_id' => $installmentPlan->category_id,
                'name' => $installmentPlan->name,
                'amount' => $installmentPlan->amount,
                'is_recurring' => false,
            ]);

            $installmentPlan->decrement('remaining_times');

            if ($installmentPlan->remaining_times === 0) {
                $installmentPlan->update(['enabled' => false]);
            }

            return redirect()->route('installment-plans.index')
                ->with('success', '支払いを記録しました。');
        }

        return redirect()->route('installment-plans.index')
            ->with('error', '残回数が0です。');
    }
}
