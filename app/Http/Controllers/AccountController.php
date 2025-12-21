<?php

namespace App\Http\Controllers;

use App\Models\Account;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AccountController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $accounts = Auth::user()->accounts()->orderBy('sort_order')->get();

        return view('accounts.index', compact('accounts'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('accounts.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'type' => 'required|string|in:card,cash,bank,other',
            'enabled' => 'boolean',
            'sort_order' => 'integer|min:0',
        ]);

        Auth::user()->accounts()->create($validated);

        return redirect()->route('accounts.index')
            ->with('success', '支払手段を登録しました。');
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Account $account)
    {
        $this->authorize('update', $account);

        return view('accounts.edit', compact('account'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Account $account)
    {
        $this->authorize('update', $account);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'type' => 'required|string|in:card,cash,bank,other',
            'enabled' => 'boolean',
            'sort_order' => 'integer|min:0',
        ]);

        $account->update($validated);

        return redirect()->route('accounts.index')
            ->with('success', '支払手段を更新しました。');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Account $account)
    {
        $this->authorize('delete', $account);
        $account->delete();

        return redirect()->route('accounts.index')
            ->with('success', '支払手段を削除しました。');
    }
}
