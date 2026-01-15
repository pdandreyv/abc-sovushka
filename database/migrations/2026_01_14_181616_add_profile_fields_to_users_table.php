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
            if (!Schema::hasColumn('users', 'phone')) {
                $table->string('phone')->nullable()->after('email');
            }
            if (!Schema::hasColumn('users', 'role')) {
                $table->string('role')->nullable()->after('phone');
            }
            if (!Schema::hasColumn('users', 'city')) {
                $table->string('city')->nullable()->after('role');
            }
            if (!Schema::hasColumn('users', 'organization')) {
                $table->string('organization')->nullable()->after('city');
            }
            if (!Schema::hasColumn('users', 'about')) {
                $table->text('about')->nullable()->after('organization');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'phone')) {
                $table->dropColumn('phone');
            }
            if (Schema::hasColumn('users', 'role')) {
                $table->dropColumn('role');
            }
            if (Schema::hasColumn('users', 'city')) {
                $table->dropColumn('city');
            }
            if (Schema::hasColumn('users', 'organization')) {
                $table->dropColumn('organization');
            }
            if (Schema::hasColumn('users', 'about')) {
                $table->dropColumn('about');
            }
        });
    }
};
