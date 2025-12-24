<?php

namespace App\Http\Controllers;

use App\Models\Transaction;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class TransactionController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $user = Auth::user();
        $year = $request->get('year', Carbon::now()->year);
        $month = $request->get('month', Carbon::now()->month);
        $type = $request->get('type'); // income or expense

        $query = Transaction::where('user_id', $user->id)
            ->forMonth($year, $month)
            ->with(['account', 'category'])
            ->orderBy('date', 'desc')
            ->orderBy('created_at', 'desc');

        if ($type) {
            $query->where('type', $type);
        }

        $transactions = $query->paginate(50);

        return view('transactions.index', compact('transactions', 'year', 'month', 'type'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $user = Auth::user();
        $accounts = $user->accounts()->where('enabled', true)->orderBy('sort_order')->get();
        $categories = $user->categories()->orderBy('sort_order')->get();

        return view('transactions.create', compact('accounts', 'categories'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'date' => 'required|date',
            'type' => 'required|in:income,expense',
            'account_id' => $request->type === 'income' ? 'nullable|exists:accounts,id' : 'required|exists:accounts,id',
            'category_id' => 'nullable|exists:categories,id',
            'name' => 'required|string|max:255',
            'amount' => 'required|numeric|min:0',
            'is_recurring' => 'boolean',
            'memo' => 'nullable|string',
            'tags' => 'nullable|string',
        ]);

        $validated['user_id'] = Auth::id();
        Transaction::create($validated);

        return redirect()->route('transactions.index')
            ->with('success', '取引を登録しました。');
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Transaction $transaction)
    {
        $this->authorize('update', $transaction);
        $user = Auth::user();
        $accounts = $user->accounts()->where('enabled', true)->orderBy('sort_order')->get();
        $categories = $user->categories()->orderBy('sort_order')->get();

        return view('transactions.edit', compact('transaction', 'accounts', 'categories'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Transaction $transaction)
    {
        $this->authorize('update', $transaction);

        $validated = $request->validate([
            'date' => 'required|date',
            'type' => 'required|in:income,expense',
            'account_id' => $request->type === 'income' ? 'nullable|exists:accounts,id' : 'required|exists:accounts,id',
            'category_id' => 'nullable|exists:categories,id',
            'name' => 'required|string|max:255',
            'amount' => 'required|numeric|min:0',
            'is_recurring' => 'boolean',
            'memo' => 'nullable|string',
            'tags' => 'nullable|string',
        ]);

        $transaction->update($validated);

        return redirect()->route('transactions.index')
            ->with('success', '取引を更新しました。');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Transaction $transaction)
    {
        $this->authorize('delete', $transaction);
        $transaction->delete();

        return redirect()->route('transactions.index')
            ->with('success', '取引を削除しました。');
    }
}
