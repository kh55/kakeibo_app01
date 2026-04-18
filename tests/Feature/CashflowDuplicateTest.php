<?php

namespace Tests\Feature;

use App\Models\CashflowEntry;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CashflowDuplicateTest extends TestCase
{
    use RefreshDatabase;

    public function test_duplicate_returns_create_form_with_prefilled_values(): void
    {
        $user = User::factory()->create();
        $entry = CashflowEntry::factory()->create([
            'user_id' => $user->id,
            'date' => '2026-04-10',
            'name' => '家賃',
            'expense_amount' => 95330,
            'income_amount' => 0,
            'memo' => 'テストメモ',
        ]);

        $response = $this->actingAs($user)
            ->get(route('cashflow.duplicate', $entry));

        $response->assertStatus(200);
        $response->assertSee('2026-05-10');
        $response->assertSee('家賃');
        $response->assertSee('95330');
        $response->assertSee('テストメモ');
    }

    public function test_duplicate_cannot_be_accessed_by_other_user(): void
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();
        $entry = CashflowEntry::factory()->create(['user_id' => $owner->id]);

        $response = $this->actingAs($other)
            ->get(route('cashflow.duplicate', $entry));

        $response->assertStatus(403);
    }
}
