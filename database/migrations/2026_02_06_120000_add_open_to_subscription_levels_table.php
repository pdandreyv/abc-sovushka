<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('subscription_levels', function (Blueprint $table) {
            $table->boolean('open')->default(false)->after('sort_order');
        });
    }

    public function down(): void
    {
        Schema::table('subscription_levels', function (Blueprint $table) {
            $table->dropColumn('open');
        });
    }
};
