<?php

namespace Tests\Feature;

use App\Models\Account;
use App\Models\Category;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ImportExportControllerTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
    }

    public function test_export_returns_csv_with_all_transactions_including_null_account(): void
    {
        $account = Account::create(['user_id' => $this->user->id, 'name' => '楽天', 'sort_order' => 1]);
        $category = Category::create(['user_id' => $this->user->id, 'name' => '食費', 'type' => 'expense']);

        Transaction::factory()->create([
            'user_id' => $this->user->id,
            'date' => '2026-06-01',
            'type' => 'expense',
            'account_id' => $account->id,
            'category_id' => $category->id,
            'name' => 'サミット',
            'amount' => 1897,
        ]);

        Transaction::factory()->create([
            'user_id' => $this->user->id,
            'date' => '2026-06-25',
            'type' => 'expense',
            'account_id' => null,
            'category_id' => $category->id,
            'name' => '不明取引',
            'amount' => 500,
        ]);

        Transaction::factory()->create([
            'user_id' => $this->user->id,
            'date' => '2026-06-30',
            'type' => 'expense',
            'account_id' => $account->id,
            'category_id' => $category->id,
            'name' => '月末取引',
            'amount' => 2000,
        ]);

        $response = $this->actingAs($this->user)
            ->get(route('import-export.export', ['year' => 2026, 'month' => 6]));

        $response->assertOk();
        $content = $response->streamedContent();

        $this->assertStringContainsString('サミット', $content);
        $this->assertStringContainsString('不明取引', $content);
        $this->assertStringContainsString('月末取引', $content);
        $this->assertStringNotContainsString('<!DOCTYPE', $content);
        $this->assertStringNotContainsString('Server Error', $content);

        $lines = array_values(array_filter(explode("\n", trim($content))));
        $this->assertCount(4, $lines, 'CSVはヘッダ1行＋データ3行になるべき');
    }

    public function test_export_writes_empty_string_when_account_is_null(): void
    {
        $category = Category::create(['user_id' => $this->user->id, 'name' => '食費', 'type' => 'expense']);

        Transaction::factory()->create([
            'user_id' => $this->user->id,
            'date' => '2026-06-15',
            'type' => 'expense',
            'account_id' => null,
            'category_id' => $category->id,
            'name' => '手動入力',
            'amount' => 1000,
        ]);

        $response = $this->actingAs($this->user)
            ->get(route('import-export.export', ['year' => 2026, 'month' => 6]));

        $response->assertOk();
        $content = $response->streamedContent();

        $lines = array_values(array_filter(explode("\n", trim($content))));
        $this->assertCount(2, $lines);

        $dataRow = str_getcsv($lines[1]);
        $this->assertSame('2026-06-15', $dataRow[0]);
        $this->assertSame('', $dataRow[1], '支払手段カラムは空文字であるべき');
        $this->assertSame('食費', $dataRow[2]);
        $this->assertSame('手動入力', $dataRow[3]);
    }
}
