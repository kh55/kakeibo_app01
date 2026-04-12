<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TransactionCategoryFilterTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
    }

    public function test_categories_are_passed_to_view(): void
    {
        Category::create(['user_id' => $this->user->id, 'name' => '食費', 'sort_order' => 1]);
        Category::create(['user_id' => $this->user->id, 'name' => '交通費', 'sort_order' => 2]);

        $response = $this->actingAs($this->user)
            ->get(route('transactions.index'));

        $response->assertOk();
        $response->assertViewHas('categories');
        $this->assertCount(2, $response->viewData('categories'));
    }

    public function test_filter_by_category_shows_only_matching_transactions(): void
    {
        $category = Category::create(['user_id' => $this->user->id, 'name' => '食費', 'sort_order' => 1]);
        $otherCategory = Category::create(['user_id' => $this->user->id, 'name' => '交通費', 'sort_order' => 2]);

        Transaction::factory()->create([
            'user_id' => $this->user->id,
            'category_id' => $category->id,
            'date' => now()->startOfMonth(),
            'name' => 'ランチ',
        ]);
        Transaction::factory()->create([
            'user_id' => $this->user->id,
            'category_id' => $otherCategory->id,
            'date' => now()->startOfMonth(),
            'name' => 'バス',
        ]);

        $response = $this->actingAs($this->user)
            ->get(route('transactions.index', [
                'year' => now()->year,
                'month' => now()->month,
                'category_id' => $category->id,
            ]));

        $response->assertOk();
        $items = $response->viewData('transactions')->items();
        $this->assertCount(1, $items);
        $this->assertSame('ランチ', $items[0]->name);
    }

    public function test_filter_by_null_shows_uncategorized_transactions(): void
    {
        $category = Category::create(['user_id' => $this->user->id, 'name' => '食費', 'sort_order' => 1]);

        Transaction::factory()->create([
            'user_id' => $this->user->id,
            'category_id' => $category->id,
            'date' => now()->startOfMonth(),
            'name' => 'ランチ',
        ]);
        Transaction::factory()->create([
            'user_id' => $this->user->id,
            'category_id' => null,
            'date' => now()->startOfMonth(),
            'name' => '未分類の取引',
        ]);

        $response = $this->actingAs($this->user)
            ->get(route('transactions.index', [
                'year' => now()->year,
                'month' => now()->month,
                'category_id' => 'null',
            ]));

        $response->assertOk();
        $items = $response->viewData('transactions')->items();
        $this->assertCount(1, $items);
        $this->assertSame('未分類の取引', $items[0]->name);
    }

    public function test_no_category_filter_shows_all_transactions(): void
    {
        $category = Category::create(['user_id' => $this->user->id, 'name' => '食費', 'sort_order' => 1]);

        Transaction::factory()->create([
            'user_id' => $this->user->id,
            'category_id' => $category->id,
            'date' => now()->startOfMonth(),
        ]);
        Transaction::factory()->create([
            'user_id' => $this->user->id,
            'category_id' => null,
            'date' => now()->startOfMonth(),
        ]);

        $response = $this->actingAs($this->user)
            ->get(route('transactions.index', [
                'year' => now()->year,
                'month' => now()->month,
            ]));

        $response->assertOk();
        $this->assertCount(2, $response->viewData('transactions')->items());
    }
}
