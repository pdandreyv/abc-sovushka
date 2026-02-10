<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('subscription_orders', function (Blueprint $table) {
            if (!Schema::hasColumn('subscription_orders', 'discount_code')) {
                $table->string('discount_code', 64)->nullable()->after('sum_without_discount')->comment('Промокод (строка), если был применён');
            }
        });
    }

    public function down(): void
    {
        Schema::table('subscription_orders', function (Blueprint $table) {
            if (Schema::hasColumn('subscription_orders', 'discount_code')) {
                $table->dropColumn('discount_code');
            }
        });
    }
};
