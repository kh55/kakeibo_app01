<?php

namespace App\Http\Controllers;

use App\Models\Budget;
use App\Models\Transaction;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\Database\QueryException;

class BudgetController extends Controller
{
    /**
     * Display a listing of the resource.
     * 指定年月・当該ユーザーの支出をカテゴリ別に集計し、各予算に実績・残り・超過有無を付与する。
     */
    public function index(Request $request)
    {
        $user = Auth::user();
        $year = (int) $request->get('year', Carbon::now()->year);
        $month = (int) $request->get('month', Carbon::now()->month);

        $budgets = Budget::where('user_id', $user->id)
            ->where('year', $year)
            ->where('month', $month)
            ->with('category')
            ->get();

        $totalsByCategory = Transaction::where('user_id', $user->id)
            ->forMonth($year, $month)
            ->expense()
            ->selectRaw('category_id, sum(amount) as total')
            ->groupBy('category_id')
            ->pluck('total', 'category_id');

        foreach ($budgets as $budget) {
            $actual = (float) ($totalsByCategory[$budget->category_id] ?? 0);
            $budget->actual_amount = $actual;
            $budget->remaining = (float) $budget->amount - $actual;
            $budget->is_over_budget = $budget->remaining < 0;
        }

        return view('budgets.index', compact('budgets', 'year', 'month'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(Request $request)
    {
        $user = Auth::user();
        $categories = $user->categories()->where('type', 'expense')->orderBy('sort_order')->get();
        $year = $request->get('year', Carbon::now()->year);
        $month = $request->get('month', Carbon::now()->month);

        return view('budgets.create', compact('categories', 'year', 'month'));
    }

    /**
     * Store a newly created resource in storage.
     * 同一 user・年・月・カテゴリの重複はバリデーションで拒否する。
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'year' => 'required|integer|min:2000|max:2100',
            'month' => 'required|integer|min:1|max:12',
            'category_id' => [
                'required',
                'exists:categories,id',
                Rule::unique('budgets', 'category_id')
                    ->where('user_id', Auth::id())
                    ->where('year', $request->input('year'))
                    ->where('month', $request->input('month')),
            ],
            'amount' => 'required|numeric|min:0',
        ], [
            'category_id.unique' => 'この年月・このカテゴリの予算は既に登録されています。',
        ]);

        $validated['user_id'] = Auth::id();

        try {
            Budget::create($validated);
        } catch (QueryException $e) {
            if ((int) ($e->errorInfo[1] ?? 0) === 1062 || str_contains($e->getMessage(), 'Duplicate entry')) {
                throw ValidationException::withMessages([
                    'category_id' => ['この年月・このカテゴリの予算は既に登録されています。'],
                ]);
            }
            throw $e;
        }

        return redirect()->route('budgets.index', ['year' => $validated['year'], 'month' => $validated['month']])
            ->with('success', '予算を登録しました。');
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Budget $budget)
    {
        $this->authorize('update', $budget);
        $user = Auth::user();
        $categories = $user->categories()->where('type', 'expense')->orderBy('sort_order')->get();

        return view('budgets.edit', compact('budget', 'categories'));
    }

    /**
     * Update the specified resource in storage.
     * 同一 user・年・月・カテゴリの別予算が存在する場合はバリデーションで拒否する。当該 budget の id は除外。
     */
    public function update(Request $request, Budget $budget)
    {
        $this->authorize('update', $budget);

        $validated = $request->validate([
            'year' => 'required|integer|min:2000|max:2100',
            'month' => 'required|integer|min:1|max:12',
            'category_id' => [
                'required',
                'exists:categories,id',
                Rule::unique('budgets', 'category_id')
                    ->where('user_id', Auth::id())
                    ->where('year', $request->input('year'))
                    ->where('month', $request->input('month'))
                    ->ignore($budget->id),
            ],
            'amount' => 'required|numeric|min:0',
        ], [
            'category_id.unique' => 'この年月・このカテゴリの予算は既に登録されています。',
        ]);

        try {
            $budget->update($validated);
        } catch (QueryException $e) {
            if ((int) ($e->errorInfo[1] ?? 0) === 1062 || str_contains($e->getMessage(), 'Duplicate entry')) {
                throw ValidationException::withMessages([
                    'category_id' => ['この年月・このカテゴリの予算は既に登録されています。'],
                ]);
            }
            throw $e;
        }

        return redirect()->route('budgets.index', ['year' => $validated['year'], 'month' => $validated['month']])
            ->with('success', '予算を更新しました。');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Budget $budget)
    {
        $this->authorize('delete', $budget);
        $budget->delete();

        return redirect()->route('budgets.index')
            ->with('success', '予算を削除しました。');
    }
}
