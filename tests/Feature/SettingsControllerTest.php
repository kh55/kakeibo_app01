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
    }
}
