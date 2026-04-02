<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SettingsControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_has_default_sort_preference_of_manual(): void
    {
        $user = User::factory()->create();

        $this->assertEquals('manual', $user->sort_preference);
        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'sort_preference' => 'manual',
        ]);
    }

    public function test_settings_page_is_displayed_for_authenticated_user(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('settings.edit'));

        $response->assertOk();
    }

    public function test_settings_page_redirects_unauthenticated_user(): void
    {
        $response = $this->get(route('settings.edit'));

        $response->assertRedirect(route('login'));
    }

    public function test_sort_preference_can_be_updated_to_frequency(): void
    {
        $user = User::factory()->create(['sort_preference' => 'manual']);

        $response = $this->actingAs($user)->put(route('settings.update'), [
            'sort_preference' => 'frequency',
        ]);

        $response->assertRedirect(route('settings.edit'));
        $this->assertEquals('frequency', $user->fresh()->sort_preference);
    }

    public function test_sort_preference_can_be_updated_to_manual(): void
    {
        $user = User::factory()->create(['sort_preference' => 'frequency']);

        $response = $this->actingAs($user)->put(route('settings.update'), [
            'sort_preference' => 'manual',
        ]);

        $response->assertRedirect(route('settings.edit'));
        $this->assertEquals('manual', $user->fresh()->sort_preference);
    }

    public function test_invalid_sort_preference_value_fails_validation(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->put(route('settings.update'), [
            'sort_preference' => 'invalid',
        ]);

        $response->assertSessionHasErrors('sort_preference');
    }

    public function test_settings_update_only_affects_authenticated_user(): void
    {
        $user = User::factory()->create(['sort_preference' => 'manual']);
        $otherUser = User::factory()->create(['sort_preference' => 'manual']);

        $this->actingAs($user)->put(route('settings.update'), [
            'sort_preference' => 'frequency',
        ]);

        $this->assertEquals('manual', $otherUser->fresh()->sort_preference);
    }
}
