<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (Schema::hasColumn('subscription_orders', 'subscription_level_ids')) {
            DB::statement("ALTER TABLE `subscription_orders` MODIFY `subscription_level_ids` VARCHAR(255) NOT NULL");
        }
        Schema::table('subscription_orders', function (Blueprint $table) {
            if (Schema::hasColumn('subscription_orders', 'date_must_pay')) {
                $table->dropColumn('date_must_pay');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasColumn('subscription_orders', 'subscription_level_ids')) {
            DB::statement("ALTER TABLE `subscription_orders` MODIFY `subscription_level_ids` JSON NOT NULL");
        }
    }
};
