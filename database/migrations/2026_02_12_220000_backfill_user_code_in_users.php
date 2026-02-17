<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (!\Illuminate\Support\Facades\Schema::hasColumn('users', 'user_code')) {
            return;
        }
        DB::statement("
            UPDATE users
            SET user_code = CONCAT(DATE_FORMAT(COALESCE(created_at, NOW()), '%Y%m%d'), id)
            WHERE user_code IS NULL OR user_code = ''
        ");
    }

    public function down(): void
    {
        // не откатываем — данные уже исправлены
    }
};
