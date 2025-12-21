<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // ダッシュボード
    Route::get('/dashboard', [\App\Http\Controllers\DashboardController::class, 'index'])->name('dashboard');

    // マスタ管理
    Route::resource('accounts', \App\Http\Controllers\AccountController::class);
    Route::resource('categories', \App\Http\Controllers\CategoryController::class);

    // 取引明細
    Route::resource('transactions', \App\Http\Controllers\TransactionController::class);

    // 予算管理
    Route::resource('budgets', \App\Http\Controllers\BudgetController::class);

    // 定期支出
    Route::resource('recurring-rules', \App\Http\Controllers\RecurringRuleController::class);
    Route::post('recurring-rules/generate', [\App\Http\Controllers\RecurringRuleController::class, 'generate'])->name('recurring-rules.generate');

    // 分割払い
    Route::resource('installment-plans', \App\Http\Controllers\InstallmentPlanController::class);
    Route::post('installment-plans/{installmentPlan}/record-payment', [\App\Http\Controllers\InstallmentPlanController::class, 'recordPayment'])->name('installment-plans.record-payment');

    // 予定表（キャッシュフロー）
    Route::resource('cashflow', \App\Http\Controllers\CashflowController::class);
    Route::post('cashflow/sync', [\App\Http\Controllers\CashflowController::class, 'sync'])->name('cashflow.sync');

    // インポート/エクスポート
    Route::get('/import-export', [\App\Http\Controllers\ImportExportController::class, 'index'])->name('import-export.index');
    Route::post('/import', [\App\Http\Controllers\ImportExportController::class, 'import'])->name('import-export.import');
    Route::get('/export', [\App\Http\Controllers\ImportExportController::class, 'export'])->name('import-export.export');
});

require __DIR__.'/auth.php';
