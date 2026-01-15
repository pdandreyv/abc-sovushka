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
        // Создание таблицы users, если она не существует
        if (!Schema::hasTable('users')) {
            Schema::create('users', function (Blueprint $table) {
                $table->id();
                $table->string('last_name'); // Фамилия
                $table->string('first_name'); // Имя
                $table->string('middle_name')->nullable(); // Отчество
                $table->string('email')->unique();
                $table->timestamp('email_verified_at')->nullable();
                $table->string('password');
                $table->rememberToken();
                $table->timestamps();
            });
        } else {
            // Если таблица существует, добавляем недостающие колонки
            Schema::table('users', function (Blueprint $table) {
                if (!Schema::hasColumn('users', 'last_name')) {
                    $table->string('last_name')->nullable();
                }
                if (!Schema::hasColumn('users', 'first_name')) {
                    $table->string('first_name')->nullable();
                }
                if (!Schema::hasColumn('users', 'middle_name')) {
                    $table->string('middle_name')->nullable();
                }
                // Если есть старое поле name, можно его удалить или оставить
                // Раскомментируйте следующую строку, если хотите удалить старое поле name:
                // if (Schema::hasColumn('users', 'name')) {
                //     $table->dropColumn('name');
                // }
            });
        }

        // Создание таблицы password_reset_tokens, если она не существует
        if (!Schema::hasTable('password_reset_tokens')) {
            Schema::create('password_reset_tokens', function (Blueprint $table) {
                $table->string('email')->primary();
                $table->string('token');
                $table->timestamp('created_at')->nullable();
            });
        }

        // Создание таблицы sessions, если она не существует
        if (!Schema::hasTable('sessions')) {
            Schema::create('sessions', function (Blueprint $table) {
                $table->string('id')->primary();
                $table->foreignId('user_id')->nullable()->index();
                $table->string('ip_address', 45)->nullable();
                $table->text('user_agent')->nullable();
                $table->longText('payload');
                $table->integer('last_activity')->index();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('users');
        Schema::dropIfExists('password_reset_tokens');
        Schema::dropIfExists('sessions');
    }
};
