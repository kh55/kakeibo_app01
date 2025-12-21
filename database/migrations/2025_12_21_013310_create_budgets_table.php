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
        Schema::create('budgets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->year('year');
            $table->unsignedTinyInteger('month'); // 1-12
            $table->foreignId('category_id')->constrained()->onDelete('cascade');
            $table->decimal('amount', 10, 2); // 予定支出額
            $table->timestamps();
            
            $table->unique(['user_id', 'year', 'month', 'category_id']);
            $table->index(['user_id', 'year', 'month']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('budgets');
    }
};
