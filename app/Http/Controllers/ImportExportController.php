<?php

namespace App\Http\Controllers;

use App\Models\Account;
use App\Models\Category;
use App\Models\Transaction;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ImportExportController extends Controller
{
    /**
     * Display import/export page.
     */
    public function index()
    {
        return view('import-export.index');
    }

    /**
     * Import transactions from CSV.
     */
    public function import(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:csv,txt',
            'year' => 'required|integer',
            'month' => 'required|integer|min:1|max:12',
        ]);

        $user = Auth::user();
        $file = $request->file('file');
        $year = $request->get('year');
        $month = $request->get('month');

        $handle = fopen($file->getRealPath(), 'r');
        fgetcsv($handle); // Skip header

        $imported = 0;
        $errors = [];

        while (($row = fgetcsv($handle)) !== false) {
            try {
                // CSV format: 日付,支払手段,分類,項目,金額,定期フラグ
                $dateStr = $row[0] ?? null;
                $accountName = $row[1] ?? null;
                $categoryName = $row[2] ?? null;
                $name = $row[3] ?? null;
                $amount = $row[4] ?? null;
                $isRecurring = isset($row[5]) && strtolower(trim($row[5])) === 'true';

                if (!$dateStr || !$accountName || !$name || !$amount) {
                    continue;
                }

                // Convert Excel serial date to date
                $date = $this->convertExcelDate($dateStr, $year, $month);

                // Find or create account
                $account = Account::firstOrCreate(
                    ['user_id' => $user->id, 'name' => $accountName],
                    ['type' => 'card', 'enabled' => true]
                );

                // Find or create category
                $category = null;
                if ($categoryName) {
                    $category = Category::firstOrCreate(
                        ['user_id' => $user->id, 'name' => $categoryName, 'type' => 'expense'],
                        ['enabled' => true]
                    );
                }

                Transaction::create([
                    'user_id' => $user->id,
                    'date' => $date,
                    'type' => 'expense',
                    'account_id' => $account->id,
                    'category_id' => $category?->id,
                    'name' => $name,
                    'amount' => floatval($amount),
                    'is_recurring' => $isRecurring,
                ]);

                $imported++;
            } catch (\Exception $e) {
                $errors[] = "行のインポートに失敗: " . $e->getMessage();
            }
        }

        fclose($handle);

        $message = "{$imported}件の取引をインポートしました。";
        if (!empty($errors)) {
            $message .= " エラー: " . count($errors) . "件";
        }

        return redirect()->route('import-export.index')
            ->with('success', $message)
            ->with('errors', $errors);
    }

    /**
     * Export transactions to CSV.
     */
    public function export(Request $request)
    {
        $user = Auth::user();
        $year = $request->get('year', Carbon::now()->year);
        $month = $request->get('month', Carbon::now()->month);

        $transactions = Transaction::where('user_id', $user->id)
            ->forMonth($year, $month)
            ->with(['account', 'category'])
            ->orderBy('date')
            ->get();

        $filename = "transactions_{$year}".str_pad($month, 2, '0', STR_PAD_LEFT).".csv";
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ];

        $callback = function () use ($transactions) {
            $file = fopen('php://output', 'w');
            fputcsv($file, ['日付', '支払手段', '分類', '項目', '金額', '定期フラグ', 'メモ']);

            foreach ($transactions as $transaction) {
                fputcsv($file, [
                    $transaction->date->format('Y-m-d'),
                    $transaction->account->name,
                    $transaction->category?->name ?? '',
                    $transaction->name,
                    $transaction->amount,
                    $transaction->is_recurring ? 'true' : 'false',
                    $transaction->memo ?? '',
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * Convert Excel serial date or date string to Carbon date.
     */
    private function convertExcelDate($dateStr, int $year, int $month): Carbon
    {
        // Try to parse as date string first
        try {
            return Carbon::parse($dateStr);
        } catch (\Exception $e) {
            // If it's a numeric value (Excel serial date)
            if (is_numeric($dateStr)) {
                // Excel serial date starts from 1900-01-01
                $excelEpoch = Carbon::create(1899, 12, 30);
                return $excelEpoch->addDays(intval($dateStr));
            }
            // Fallback to first day of specified month
            return Carbon::create($year, $month, 1);
        }
    }
}
