<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Последние 4 цифры карты из ЮKassa (payment_method.card.last4).
     */
    public function up(): void
    {
        Schema::table('subscription_orders', function (Blueprint $table) {
            $table->string('card_last4', 4)->nullable()->after('hash')->comment('Последние 4 цифры карты (ЮKassa)');
        });
    }

    public function down(): void
    {
        Schema::table('subscription_orders', function (Blueprint $table) {
            $table->dropColumn('card_last4');
        });
    }
};
