<?php

namespace Tests\Feature;

// use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    /**
     * A basic test example.
     * セキュリティ対策により、トップページは404を返す
     */
    public function test_the_application_returns_a_successful_response(): void
    {
        // セキュリティ対策: トップページは404を返す
        $response = $this->get('/');
        $response->assertStatus(404);

        // ログインページは正常にアクセスできることを確認
        $loginResponse = $this->get('/login');
        $loginResponse->assertStatus(200);
    }
}
