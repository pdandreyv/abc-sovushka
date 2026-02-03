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
        Schema::table('subscription_orders', function (Blueprint $table) {
            if (!Schema::hasColumn('subscription_orders', 'levels')) {
                $table->string('levels')->nullable()->after('subscription_level_ids');
            }
            if (!Schema::hasColumn('subscription_orders', 'paid')) {
                $table->boolean('paid')->default(false)->after('levels');
            }
            if (!Schema::hasColumn('subscription_orders', 'date_paid')) {
                $table->dateTime('date_paid')->nullable()->after('paid');
            }
            if (!Schema::hasColumn('subscription_orders', 'tariff')) {
                $table->unsignedInteger('tariff')->nullable()->after('date_paid');
            }
            if (!Schema::hasColumn('subscription_orders', 'date_till')) {
                $table->date('date_till')->nullable()->after('tariff');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('subscription_orders', function (Blueprint $table) {
            if (Schema::hasColumn('subscription_orders', 'levels')) {
                $table->dropColumn('levels');
            }
            if (Schema::hasColumn('subscription_orders', 'paid')) {
                $table->dropColumn('paid');
            }
            if (Schema::hasColumn('subscription_orders', 'date_paid')) {
                $table->dropColumn('date_paid');
            }
            if (Schema::hasColumn('subscription_orders', 'tariff')) {
                $table->dropColumn('tariff');
            }
            if (Schema::hasColumn('subscription_orders', 'date_till')) {
                $table->dropColumn('date_till');
            }
        });
    }
};
