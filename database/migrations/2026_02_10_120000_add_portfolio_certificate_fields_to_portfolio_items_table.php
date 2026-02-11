<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('portfolio_items', function (Blueprint $table) {
            $table->unsignedBigInteger('subscription_level_id')->nullable()->after('display');
            $table->date('date_from')->nullable()->after('subscription_level_id');
            $table->date('date_to')->nullable()->after('date_from');
            $table->unsignedBigInteger('user_id')->nullable()->after('date_to');
        });
    }

    public function down(): void
    {
        Schema::table('portfolio_items', function (Blueprint $table) {
            $table->dropColumn(['subscription_level_id', 'date_from', 'date_to', 'user_id']);
        });
    }
};
