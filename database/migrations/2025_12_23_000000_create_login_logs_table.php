<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('login_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->onDelete('cascade');
            $table->string('email'); // ログイン試行時のメールアドレス（失敗時も記録）
            $table->string('ip_address', 45); // IPv6対応のため45文字
            $table->text('user_agent')->nullable(); // User-Agent文字列
            $table->string('status'); // 'success' または 'failed'
            $table->timestamp('login_at'); // ログイン試行日時
            $table->timestamps();

            // インデックスを追加（検索パフォーマンス向上のため）
            $table->index('user_id');
            $table->index('email');
            $table->index('status');
            $table->index('login_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('login_logs');
    }
};
