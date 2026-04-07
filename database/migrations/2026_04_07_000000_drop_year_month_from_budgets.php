<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // MySQL は DDL トランザクションがないため、過去の失敗で部分適用済みの可能性がある。
        // 各ステップを独立して実行し、既に適用済みの場合は例外を無視する。

        // Step 1: 新しいユニーク制約を追加（user_id FK インデックスとして機能させるため先に追加）
        try {
            Schema::table('budgets', function (Blueprint $table) {
                $table->unique(['user_id', 'category_id']);
            });
        } catch (\Exception $e) {
            // 既に存在する場合は無視
        }

        // Step 2: 古いユニーク制約を削除
        try {
            Schema::table('budgets', function (Blueprint $table) {
                $table->dropUnique(['user_id', 'year', 'month', 'category_id']);
            });
        } catch (\Exception $e) {
            // 既に削除済みの場合は無視
        }

        // Step 3: 古いインデックスを削除
        try {
            Schema::table('budgets', function (Blueprint $table) {
                $table->dropIndex('budgets_user_id_year_month_index');
            });
        } catch (\Exception $e) {
            // 既に削除済みの場合は無視
        }

        // Step 4: year/month カラムを削除（存在する場合のみ）
        if (Schema::hasColumn('budgets', 'year')) {
            Schema::table('budgets', function (Blueprint $table) {
                $table->dropColumn(['year', 'month']);
            });
        }
    }

    public function down(): void
    {
        Schema::table('budgets', function (Blueprint $table) {
            $table->dropUnique(['user_id', 'category_id']);
            $table->year('year')->nullable()->after('user_id');
            $table->unsignedTinyInteger('month')->nullable()->after('year');
            $table->unique(['user_id', 'year', 'month', 'category_id']);
            $table->index(['user_id', 'year', 'month'], 'budgets_user_id_year_month_index');
        });
    }
};
