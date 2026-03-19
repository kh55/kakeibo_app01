<?php

namespace Tests\Feature;

use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TransactionPerPageTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
        // 25件の取引を作成（20件選択時にページ2が存在することを確認するため）
        Transaction::factory()->count(25)->create([
            'user_id' => $this->user->id,
            'date' => now()->startOfMonth(),
        ]);
    }

    public function test_default_per_page_is_50(): void
    {
        $response = $this->actingAs($this->user)
            ->get(route('transactions.index'));

        $response->assertOk();
        $response->assertViewHas('perPage', 50);
    }

    public function test_per_page_20_shows_correct_count(): void
    {
        $response = $this->actingAs($this->user)
            ->get(route('transactions.index', ['per_page' => 20]));

        $response->assertOk();
        $response->assertViewHas('perPage', 20);
        $this->assertCount(20, $response->viewData('transactions')->items());
    }

    public function test_per_page_100_is_valid(): void
    {
        $response = $this->actingAs($this->user)
            ->get(route('transactions.index', ['per_page' => 100]));

        $response->assertOk();
        $response->assertViewHas('perPage', 100);
    }

    public function test_invalid_per_page_falls_back_to_50(): void
    {
        $response = $this->actingAs($this->user)
            ->get(route('transactions.index', ['per_page' => 999]));

        $response->assertOk();
        $response->assertViewHas('perPage', 50);
    }

    public function test_string_per_page_falls_back_to_50(): void
    {
        $response = $this->actingAs($this->user)
            ->get(route('transactions.index', ['per_page' => 'abc']));

        $response->assertOk();
        $response->assertViewHas('perPage', 50);
    }
}
