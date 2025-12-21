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
        Schema::create('transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->date('date');
            $table->enum('type', ['income', 'expense']);
            $table->foreignId('account_id')->constrained()->onDelete('restrict');
            $table->foreignId('category_id')->nullable()->constrained()->onDelete('set null');
            $table->string('name'); // 項目名
            $table->decimal('amount', 10, 2);
            $table->boolean('is_recurring')->default(false);
            $table->text('memo')->nullable();
            $table->string('tags')->nullable(); // カンマ区切り
            $table->softDeletes();
            $table->timestamps();
            
            $table->index(['user_id', 'date']);
            $table->index(['user_id', 'type', 'date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transactions');
    }
};
