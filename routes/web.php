<?php

use App\Http\Controllers\AccountController;
use App\Http\Controllers\BudgetController;
use App\Http\Controllers\CashflowController;
use App\Http\Controllers\CategoryComparisonController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ImportExportController;
use App\Http\Controllers\InstallmentPlanController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\RecurringRuleController;
use App\Http\Controllers\SettingsController;
use App\Http\Controllers\TransactionController;
use Illuminate\Support\Facades\Route;

// セキュリティ対策: トップページへのアクセスを404に設定
Route::get('/', function () {
    abort(404);
});

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
    Route::get('/profile/login-logs', [ProfileController::class, 'loginLogs'])->name('profile.login-logs');

    // 設定
    Route::get('/settings', [SettingsController::class, 'edit'])->name('settings.edit');
    Route::put('/settings', [SettingsController::class, 'update'])->name('settings.update');

    // ダッシュボード
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/annual-summary', [DashboardController::class, 'annualSummary'])->name('annual-summary.index');
    Route::get('/category-comparison', [CategoryComparisonController::class, 'index'])->name('category-comparison.index');

    // マスタ管理
    Route::resource('accounts', AccountController::class);
    Route::resource('categories', CategoryController::class);

    // 取引明細
    Route::resource('transactions', TransactionController::class);

    // 予算管理
    Route::resource('budgets', BudgetController::class);

    // 定期支出
    Route::resource('recurring-rules', RecurringRuleController::class);
    Route::post('recurring-rules/generate', [RecurringRuleController::class, 'generate'])->name('recurring-rules.generate');

    // 分割払い
    Route::resource('installment-plans', InstallmentPlanController::class);
    Route::post('installment-plans/{installmentPlan}/record-payment', [InstallmentPlanController::class, 'recordPayment'])->name('installment-plans.record-payment');

    // 予定表（キャッシュフロー）
    Route::get('cashflow/{cashflowEntry}/duplicate', [CashflowController::class, 'duplicate'])->name('cashflow.duplicate');
    Route::post('cashflow/sync', [CashflowController::class, 'sync'])->name('cashflow.sync');
    Route::resource('cashflow', CashflowController::class)->parameters(['cashflow' => 'cashflowEntry']);

    // インポート/エクスポート
    Route::get('/import-export', [ImportExportController::class, 'index'])->name('import-export.index');
    Route::post('/import', [ImportExportController::class, 'import'])->name('import-export.import');
    Route::get('/export', [ImportExportController::class, 'export'])->name('import-export.export');
});

require __DIR__.'/auth.php';
