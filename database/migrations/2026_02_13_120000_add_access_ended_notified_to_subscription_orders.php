<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('subscription_orders') && ! Schema::hasColumn('subscription_orders', 'access_ended_notified_at')) {
            Schema::table('subscription_orders', function (Blueprint $table) {
                $table->timestamp('access_ended_notified_at')->nullable()->after('date_till');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('subscription_orders', 'access_ended_notified_at')) {
            Schema::table('subscription_orders', function (Blueprint $table) {
                $table->dropColumn('access_ended_notified_at');
            });
        }
    }
};
