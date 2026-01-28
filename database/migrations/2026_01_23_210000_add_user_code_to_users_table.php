<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'user_code')) {
                $table->string('user_code')->nullable()->after('id');
            }
        });

        DB::statement("
            UPDATE users
            SET user_code = CONCAT(DATE_FORMAT(created_at, '%Y%m%d'), id)
            WHERE (user_code IS NULL OR user_code = '')
              AND created_at IS NOT NULL
        ");
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'user_code')) {
                $table->dropColumn('user_code');
            }
        });
    }
};
