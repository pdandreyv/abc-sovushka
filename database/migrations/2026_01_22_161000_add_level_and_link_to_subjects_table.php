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
        Schema::table('subjects', function (Blueprint $table) {
            $table->unsignedBigInteger('subscription_level_id')->nullable()->after('id');
            $table->string('link')->nullable()->after('title');

            $table->foreign('subscription_level_id')
                ->references('id')
                ->on('subscription_levels')
                ->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('subjects', function (Blueprint $table) {
            $table->dropForeign(['subscription_level_id']);
            $table->dropColumn('subscription_level_id');
            $table->dropColumn('link');
        });
    }
};
