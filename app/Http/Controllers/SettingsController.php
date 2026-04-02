<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SettingsController extends Controller
{
    public function edit(Request $request): View
    {
        return view('settings.edit', [
            'user' => $request->user(),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'sort_preference' => 'required|in:manual,frequency',
        ]);

        $request->user()->update($validated);

        return redirect()->route('settings.edit')->with('status', 'settings-updated');
    }
}
