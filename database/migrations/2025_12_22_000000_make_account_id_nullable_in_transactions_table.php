<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // SQLiteではchange()メソッドがサポートされていないため、
        // 外部キー制約を削除してからカラムを変更し、再度追加する
        if (DB::getDriverName() === 'sqlite') {
            // SQLiteでは外部キー制約を削除
            DB::statement('PRAGMA foreign_keys=off;');
            
            // 一時テーブルを作成
            DB::statement('CREATE TABLE transactions_new (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                user_id INTEGER NOT NULL,
                date DATE NOT NULL,
                type TEXT NOT NULL CHECK(type IN (\'income\', \'expense\')),
                account_id INTEGER NULL,
                category_id INTEGER NULL,
                name VARCHAR(255) NOT NULL,
                amount DECIMAL(10,2) NOT NULL,
                is_recurring BOOLEAN NOT NULL DEFAULT 0,
                memo TEXT NULL,
                tags VARCHAR(255) NULL,
                deleted_at DATETIME NULL,
                created_at DATETIME NULL,
                updated_at DATETIME NULL,
                FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
                FOREIGN KEY (account_id) REFERENCES accounts(id) ON DELETE RESTRICT,
                FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE SET NULL
            );');
            
            // データをコピー
            DB::statement('INSERT INTO transactions_new SELECT * FROM transactions;');
            
            // 古いテーブルを削除
            DB::statement('DROP TABLE transactions;');
            
            // 新しいテーブルをリネーム
            DB::statement('ALTER TABLE transactions_new RENAME TO transactions;');
            
            // インデックスを再作成
            DB::statement('CREATE INDEX transactions_user_id_date_index ON transactions(user_id, date);');
            DB::statement('CREATE INDEX transactions_user_id_type_date_index ON transactions(user_id, type, date);');
            
            DB::statement('PRAGMA foreign_keys=on;');
        } else {
            Schema::table('transactions', function (Blueprint $table) {
                $table->foreignId('account_id')->nullable()->change();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (DB::getDriverName() === 'sqlite') {
            // SQLiteでは外部キー制約を削除
            DB::statement('PRAGMA foreign_keys=off;');
            
            // 一時テーブルを作成（account_idをNOT NULLに）
            DB::statement('CREATE TABLE transactions_new (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                user_id INTEGER NOT NULL,
                date DATE NOT NULL,
                type TEXT NOT NULL CHECK(type IN (\'income\', \'expense\')),
                account_id INTEGER NOT NULL,
                category_id INTEGER NULL,
                name VARCHAR(255) NOT NULL,
                amount DECIMAL(10,2) NOT NULL,
                is_recurring BOOLEAN NOT NULL DEFAULT 0,
                memo TEXT NULL,
                tags VARCHAR(255) NULL,
                deleted_at DATETIME NULL,
                created_at DATETIME NULL,
                updated_at DATETIME NULL,
                FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
                FOREIGN KEY (account_id) REFERENCES accounts(id) ON DELETE RESTRICT,
                FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE SET NULL
            );');
            
            // account_idがNULLのレコードを除外してデータをコピー
            DB::statement('INSERT INTO transactions_new SELECT * FROM transactions WHERE account_id IS NOT NULL;');
            
            // 古いテーブルを削除
            DB::statement('DROP TABLE transactions;');
            
            // 新しいテーブルをリネーム
            DB::statement('ALTER TABLE transactions_new RENAME TO transactions;');
            
            // インデックスを再作成
            DB::statement('CREATE INDEX transactions_user_id_date_index ON transactions(user_id, date);');
            DB::statement('CREATE INDEX transactions_user_id_type_date_index ON transactions(user_id, type, date);');
            
            DB::statement('PRAGMA foreign_keys=on;');
        } else {
            Schema::table('transactions', function (Blueprint $table) {
                $table->foreignId('account_id')->nullable(false)->change();
            });
        }
    }
};

