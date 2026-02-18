<?php

namespace Database\Seeders;

use App\Models\Account;
use App\Models\Category;
use App\Models\Transaction;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Hash;

/**
 * ローカル環境専用のテストデータシーダー.
 *
 * 本番では絶対に実行されません。APP_ENV=local のときのみ実行されます。
 * ページング確認用に取引を 65 件作成します（1 ページ 50 件のため 2 ページ目が表示されます）。
 */
class LocalTestDataSeeder extends Seeder
{
    private const MIN_TRANSACTIONS_FOR_PAGINATION = 65;

    public function run(): void
    {
        // 本番・ステージングでは絶対に実行しない（ローカル専用）
        if (! app()->environment('local')) {
            $this->command->warn('LocalTestDataSeeder: Skipped (APP_ENV is not "local"). This seeder must never run in production.');

            return;
        }

        $user = User::firstOrCreate(
            ['email' => 'test@example.com'],
            [
                'name' => 'Test User',
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
            ]
        );

        $accounts = $this->ensureAccounts($user);
        $categories = $this->ensureCategories($user);

        $year = (int) Carbon::now()->year;
        $month = (int) Carbon::now()->month;
        $this->persistSeedInfo($year, $month);

        $existingCount = Transaction::where('user_id', $user->id)->forMonth($year, $month)->count();

        if ($existingCount >= self::MIN_TRANSACTIONS_FOR_PAGINATION) {
            $this->command->info("LocalTestDataSeeder: Already have {$existingCount} transactions for {$year}-{$month}. Skipping transaction creation.");

            return;
        }

        $toCreate = self::MIN_TRANSACTIONS_FOR_PAGINATION - $existingCount;
        $expenseCategories = $categories->where('type', 'expense');
        $incomeCategories = $categories->where('type', 'income');

        for ($i = 0; $i < $toCreate; $i++) {
            $isIncome = $i % 5 === 0; // 約20%が収入
            $date = Carbon::create($year, $month, 1)->addDays($i % 28);

            Transaction::create([
                'user_id' => $user->id,
                'date' => $date,
                'type' => $isIncome ? 'income' : 'expense',
                'account_id' => $isIncome ? null : $accounts->random()->id,
                'category_id' => $isIncome ? $incomeCategories->random()->id : $expenseCategories->random()->id,
                'name' => $isIncome ? '給料' : ['食費', '日用品', '交通費', '光熱費', '通信費'][$i % 5],
                'amount' => $isIncome ? 280000 : [500, 1200, 350, 890, 2100][$i % 5],
                'is_recurring' => false,
                'memo' => 'ローカルテストデータ',
                'tags' => null,
            ]);
        }

        $this->command->info("LocalTestDataSeeder: Created {$toCreate} transactions for {$year}-{$month}. Total in month: ".Transaction::where('user_id', $user->id)->forMonth($year, $month)->count());
    }

    private function persistSeedInfo(int $year, int $month): void
    {
        if (! app()->environment('local')) {
            return;
        }

        $path = storage_path('app/local-test-data.json');

        try {
            File::ensureDirectoryExists(dirname($path));
            File::put($path, json_encode([
                'yearMonth' => sprintf('%04d-%02d', $year, $month),
                'seededAt' => now()->toIso8601String(),
            ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
        } catch (\Throwable $e) {
            // ローカル専用の補助情報なので、失敗してもシーディング自体は継続する
        }
    }

    private function ensureAccounts(User $user): \Illuminate\Support\Collection
    {
        $names = ['現金', 'メインバンク', 'クレジットカード'];
        $types = ['cash', 'bank', 'card'];

        $accounts = collect();
        foreach ($names as $idx => $name) {
            $accounts->push(Account::firstOrCreate(
                [
                    'user_id' => $user->id,
                    'name' => $name,
                ],
                [
                    'type' => $types[$idx],
                    'enabled' => true,
                    'sort_order' => $idx + 1,
                ]
            ));
        }

        return $accounts;
    }

    private function ensureCategories(User $user): \Illuminate\Support\Collection
    {
        $items = [
            ['name' => '食費', 'type' => 'expense', 'color' => '#ef4444'],
            ['name' => '日用品', 'type' => 'expense', 'color' => '#f97316'],
            ['name' => '交通費', 'type' => 'expense', 'color' => '#eab308'],
            ['name' => '光熱費', 'type' => 'expense', 'color' => '#22c55e'],
            ['name' => '通信費', 'type' => 'expense', 'color' => '#3b82f6'],
            ['name' => '給料', 'type' => 'income', 'color' => '#10b981'],
            ['name' => '副業', 'type' => 'income', 'color' => '#14b8a6'],
        ];

        $categories = collect();
        foreach ($items as $idx => $item) {
            $categories->push(Category::firstOrCreate(
                [
                    'user_id' => $user->id,
                    'name' => $item['name'],
                ],
                [
                    'type' => $item['type'],
                    'color' => $item['color'],
                    'sort_order' => $idx + 1,
                ]
            ));
        }

        return $categories;
    }
}
