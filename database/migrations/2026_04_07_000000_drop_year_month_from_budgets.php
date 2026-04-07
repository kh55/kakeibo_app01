<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('budgets', function (Blueprint $table) {
            $table->dropUnique(['user_id', 'year', 'month', 'category_id']);
            $table->dropIndex(['user_id', 'year', 'month']);
            $table->dropColumn(['year', 'month']);
            $table->unique(['user_id', 'category_id']);
        });
    }

    public function down(): void
    {
        Schema::table('budgets', function (Blueprint $table) {
            $table->dropUnique(['user_id', 'category_id']);
            $table->year('year')->after('user_id');
            $table->unsignedTinyInteger('month')->after('year');
            $table->unique(['user_id', 'year', 'month', 'category_id']);
            $table->index(['user_id', 'year', 'month']);
        });
    }
};
