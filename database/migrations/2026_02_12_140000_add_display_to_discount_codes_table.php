<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * display: при выключении промокод не действует, но не удаляется и код не меняется.
     */
    public function up(): void
    {
        Schema::table('discount_codes', function (Blueprint $table) {
            $table->boolean('display')->default(true)->after('discount_percent');
        });
    }

    public function down(): void
    {
        Schema::table('discount_codes', function (Blueprint $table) {
            $table->dropColumn('display');
        });
    }
};
