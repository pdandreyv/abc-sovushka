<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Тексты блока «Демо-уроки» на странице уровня подписки (subjects/{level}).
     */
    public function up(): void
    {
        Schema::table('subscription_levels', function (Blueprint $table) {
            $table->string('demo_block_title')->nullable()->after('demo_file');
            $table->text('demo_block_description')->nullable()->after('demo_block_title');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('subscription_levels', function (Blueprint $table) {
            $table->dropColumn(['demo_block_title', 'demo_block_description']);
        });
    }
};
