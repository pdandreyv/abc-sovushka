<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * display=1 — уровень показывается в выборе подписок на странице /subscriptions.
     * display=0 — уровень скрыт из выбора, в меню ЛК по-прежнему показываются пункты с open=1.
     */
    public function up(): void
    {
        Schema::table('subscription_levels', function (Blueprint $table) {
            $table->boolean('display')->default(true)->after('open');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('subscription_levels', function (Blueprint $table) {
            $table->dropColumn('display');
        });
    }
};
