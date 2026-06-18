<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Transaction;
use App\Models\User;
use App\Services\CategoryComparisonService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CategoryComparisonServiceTest extends TestCase
{
    use RefreshDatabase;

    private CategoryComparisonService $service;

    private User $user;

    private Category $category;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new CategoryComparisonService;
        $this->user = User::factory()->create();
        $this->category = Category::create([
            'user_id' => $this->user->id,
            'name' => '食費',
            'type' => 'expense',
            'color' => '#000000',
            'sort_order' => 1,
        ]);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    private function expense(string $date, float $amount, ?int $categoryId = null): void
    {
        Transaction::factory()->create([
            'user_id' => $this->user->id,
            'type' => 'expense',
            'category_id' => $categoryId ?? $this->category->id,
            'amount' => $amount,
            'date' => $date,
        ]);
    }

    public function test_daily_comparison_aggregates_current_and_previous_month(): void
    {
        // 基準月: 2025-04（過去月なので未来日 null ロジックは効かない）
        $this->expense('2025-04-10', 1000);
        $this->expense('2025-04-10', 500); // 同日合算
        $this->expense('2025-04-20', 2000);
        $this->expense('2025-03-15', 800); // 前月

        $result = $this->service->getDailyComparison(
            $this->user,
            $this->category->id,
            Carbon::parse('2025-04-01'),
        );

        $this->assertSame('2025-04', $result['currentLabel']);
        $this->assertSame('2025-03', $result['previousLabel']);
        $this->assertSame(1, $result['labels'][0]);
        // 4/10 = index 9, 合算 1500
        $this->assertSame(1500.0, $result['current'][9]);
        // 4/20 = index 19
        $this->assertSame(2000.0, $result['current'][19]);
        // 取引のない 4/1 = index 0 は 0.0
        $this->assertSame(0.0, $result['current'][0]);
        // 前月 3/15 = index 14
        $this->assertSame(800.0, $result['previous'][14]);
    }

    public function test_daily_comparison_nulls_nonexistent_days_for_shorter_month(): void
    {
        // 基準月 2025-02（28日）, 前月 2025-01（31日）
        $result = $this->service->getDailyComparison(
            $this->user,
            $this->category->id,
            Carbon::parse('2025-02-01'),
        );

        // labels は最大日数 31 まで
        $this->assertCount(31, $result['labels']);
        // 2月に存在しない 29,30,31日 (index 28,29,30) は null
        $this->assertNull($result['current'][28]);
        $this->assertNull($result['current'][30]);
        // 1月は31日まで存在するので previous は 0.0
        $this->assertSame(0.0, $result['previous'][30]);
    }

    public function test_daily_comparison_nulls_future_days_in_current_month(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-06-15'));

        $this->expense('2026-06-10', 1000);

        $result = $this->service->getDailyComparison(
            $this->user,
            $this->category->id,
            Carbon::parse('2026-06-01'),
        );

        // 6/10 = index 9
        $this->assertSame(1000.0, $result['current'][9]);
        // 今日(6/15)より後の 6/16 = index 15 は null
        $this->assertNull($result['current'][15]);
        // 前月 2026-05 は実日付として確定しているので index 15 は 0.0
        $this->assertSame(0.0, $result['previous'][15]);
    }

    public function test_daily_comparison_excludes_income_and_other_categories(): void
    {
        $other = Category::create([
            'user_id' => $this->user->id,
            'name' => '日用品',
            'type' => 'expense',
            'color' => '#111111',
            'sort_order' => 2,
        ]);
        // 別カテゴリの支出
        $this->expense('2025-04-10', 9999, $other->id);
        // 収入（同カテゴリだが type=income）
        Transaction::factory()->create([
            'user_id' => $this->user->id,
            'type' => 'income',
            'category_id' => $this->category->id,
            'amount' => 7777,
            'date' => '2025-04-10',
        ]);

        $result = $this->service->getDailyComparison(
            $this->user,
            $this->category->id,
            Carbon::parse('2025-04-01'),
        );

        // 別カテゴリ・収入は混入しない → 4/10 は 0.0
        $this->assertSame(0.0, $result['current'][9]);
    }

    public function test_daily_comparison_excludes_other_users(): void
    {
        $other = User::factory()->create();
        $otherCategory = Category::create([
            'user_id' => $other->id,
            'name' => '食費',
            'type' => 'expense',
            'color' => '#000000',
            'sort_order' => 1,
        ]);
        Transaction::factory()->create([
            'user_id' => $other->id,
            'type' => 'expense',
            'category_id' => $otherCategory->id,
            'amount' => 9999,
            'date' => '2025-04-10',
        ]);

        $result = $this->service->getDailyComparison(
            $this->user,
            $this->category->id,
            Carbon::parse('2025-04-01'),
        );

        $this->assertSame(0.0, $result['current'][9]);
    }

    public function test_daily_comparison_excludes_soft_deleted(): void
    {
        $this->expense('2025-04-10', 1000);
        Transaction::where('user_id', $this->user->id)->delete();

        $result = $this->service->getDailyComparison(
            $this->user,
            $this->category->id,
            Carbon::parse('2025-04-01'),
        );

        $this->assertSame(0.0, $result['current'][9]);
    }

    public function test_monthly_comparison_aggregates_current_and_previous_year(): void
    {
        // 基準年 2025（now=2026 のため過去年扱い、未来月 null は効かない）
        $this->expense('2025-04-10', 1000);
        $this->expense('2025-04-20', 500); // 4月合算
        $this->expense('2025-05-01', 2000);
        $this->expense('2024-04-15', 800); // 前年4月

        $result = $this->service->getMonthlyComparison($this->user, $this->category->id, 2025);

        $this->assertSame('2025', $result['currentLabel']);
        $this->assertSame('2024', $result['previousLabel']);
        $this->assertSame('1月', $result['labels'][0]);
        $this->assertSame('12月', $result['labels'][11]);
        // 4月 = index 3, 合算 1500
        $this->assertSame(1500.0, $result['current'][3]);
        // 5月 = index 4
        $this->assertSame(2000.0, $result['current'][4]);
        // 取引のない 1月 = index 0 は 0.0
        $this->assertSame(0.0, $result['current'][0]);
        // 前年4月 = index 3
        $this->assertSame(800.0, $result['previous'][3]);
    }

    public function test_monthly_comparison_nulls_future_months_in_current_year(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-06-15'));

        $this->expense('2026-03-10', 1000);

        $result = $this->service->getMonthlyComparison($this->user, $this->category->id, 2026);

        // 3月 = index 2
        $this->assertSame(1000.0, $result['current'][2]);
        // 現在月(6月)より後の 7月 = index 6 は null
        $this->assertNull($result['current'][6]);
        // 6月 = index 5 は今月まで含むので 0.0
        $this->assertSame(0.0, $result['current'][5]);
    }

    public function test_monthly_comparison_excludes_income_other_users_and_soft_deleted(): void
    {
        // 収入
        Transaction::factory()->create([
            'user_id' => $this->user->id,
            'type' => 'income',
            'category_id' => $this->category->id,
            'amount' => 7777,
            'date' => '2025-04-10',
        ]);
        // 別ユーザー
        $other = User::factory()->create();
        $otherCategory = Category::create([
            'user_id' => $other->id,
            'name' => '食費',
            'type' => 'expense',
            'color' => '#000000',
            'sort_order' => 1,
        ]);
        Transaction::factory()->create([
            'user_id' => $other->id,
            'type' => 'expense',
            'category_id' => $otherCategory->id,
            'amount' => 9999,
            'date' => '2025-04-10',
        ]);
        // ソフトデリート
        $this->expense('2025-04-10', 1000);
        Transaction::where('user_id', $this->user->id)->where('type', 'expense')->delete();

        $result = $this->service->getMonthlyComparison($this->user, $this->category->id, 2025);

        // すべて除外されるので 4月 = index 3 は 0.0
        $this->assertSame(0.0, $result['current'][3]);
    }
}
