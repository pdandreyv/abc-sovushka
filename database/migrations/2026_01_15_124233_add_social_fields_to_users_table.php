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
        Schema::table('users', function (Blueprint $table) {
            $table->string('social_id', 50)->nullable()->after('email');
            $table->string('social_provider', 20)->nullable()->after('social_id');
            $table->index(['social_id', 'social_provider']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex(['social_id', 'social_provider']);
            $table->dropColumn(['social_id', 'social_provider']);
        });
    }
};
