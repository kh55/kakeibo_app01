<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Transaction;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CategoryComparisonControllerTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    private function expenseCategory(string $name, int $sort): Category
    {
        return Category::create([
            'user_id' => $this->user->id,
            'name' => $name,
            'type' => 'expense',
            'color' => '#000000',
            'sort_order' => $sort,
        ]);
    }

    public function test_guest_is_redirected_to_login(): void
    {
        $response = $this->get(route('category-comparison.index'));

        $response->assertRedirect(route('login'));
    }

    public function test_authenticated_user_can_view_page(): void
    {
        $this->expenseCategory('食費', 1);

        $response = $this->actingAs($this->user)->get(route('category-comparison.index'));

        $response->assertOk();
        $response->assertViewHas('daily');
        $response->assertViewHas('monthly');
    }

    public function test_only_expense_categories_are_listed(): void
    {
        $this->expenseCategory('食費', 1);
        Category::create([
            'user_id' => $this->user->id,
            'name' => '給与',
            'type' => 'income',
            'color' => '#000000',
            'sort_order' => 2,
        ]);

        $response = $this->actingAs($this->user)->get(route('category-comparison.index'));

        $categories = $response->viewData('categories');
        $this->assertCount(1, $categories);
        $this->assertSame('食費', $categories->first()->name);
    }

    public function test_defaults_to_current_month_and_year(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-06-18'));
        $this->expenseCategory('食費', 1);

        $response = $this->actingAs($this->user)->get(route('category-comparison.index'));

        $response->assertViewHas('baseMonth', '2026-06');
        $response->assertViewHas('baseYear', 2026);
    }

    public function test_falls_back_to_first_category_for_invalid_id(): void
    {
        $first = $this->expenseCategory('食費', 1);

        $response = $this->actingAs($this->user)
            ->get(route('category-comparison.index', ['category_id' => 999999]));

        $response->assertOk();
        $this->assertSame($first->id, $response->viewData('selectedCategory')->id);
    }

    public function test_shows_empty_state_when_no_expense_category(): void
    {
        $response = $this->actingAs($this->user)->get(route('category-comparison.index'));

        $response->assertOk();
        $this->assertNull($response->viewData('selectedCategory'));
    }

    public function test_page_shows_period_totals_and_change_labels(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-06-18'));
        $category = $this->expenseCategory('食費', 1);
        Transaction::factory()->create([
            'user_id' => $this->user->id,
            'type' => 'expense',
            'category_id' => $category->id,
            'amount' => 1234,
            'date' => '2026-06-10',
        ]);

        $response = $this->actingAs($this->user)->get(route('category-comparison.index'));

        $response->assertOk();
        // 合計サマリの増減ラベル（日毎・月毎の両方が DOM に存在）
        $response->assertSee('前月比');
        $response->assertSee('前年比');
        // 当月合計 1,234円 が表示される
        $response->assertSee('1,234円');
    }
}
