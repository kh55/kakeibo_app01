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
        Schema::create('installment_plans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('name'); // 案件名（例: iPad, Mac mini）
            $table->date('start_date');
            $table->unsignedTinyInteger('pay_day'); // 支払日（1-31）
            $table->decimal('amount', 10, 2); // 毎月額
            $table->unsignedInteger('times'); // 総支払回数
            $table->unsignedInteger('remaining_times'); // 残回数
            $table->foreignId('account_id')->nullable()->constrained()->onDelete('set null');
            $table->foreignId('category_id')->nullable()->constrained()->onDelete('set null');
            $table->boolean('enabled')->default(true);
            $table->timestamps();
            
            $table->index(['user_id', 'enabled']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('installment_plans');
    }
};
