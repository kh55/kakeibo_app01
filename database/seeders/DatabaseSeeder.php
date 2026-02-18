<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
        ]);

        // ローカル専用テストデータ（ページング確認用 65 件など）。本番では絶対に実行されない。
        if (app()->environment('local')) {
            $this->call(LocalTestDataSeeder::class);
        }
    }
}
