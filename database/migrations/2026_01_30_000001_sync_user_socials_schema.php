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
        if (!Schema::hasTable('user_socials')) {
            return;
        }

        Schema::table('user_socials', function (Blueprint $table) {
            if (!Schema::hasColumn('user_socials', 'last_visit')) {
                $table->dateTime('last_visit')->nullable()->after('updated_at');
            }
            if (!Schema::hasColumn('user_socials', 'user')) {
                $table->unsignedInteger('user')->nullable()->after('last_visit');
            }
            if (!Schema::hasColumn('user_socials', 'type')) {
                $table->unsignedTinyInteger('type')->nullable()->after('user');
            }
            if (!Schema::hasColumn('user_socials', 'uid')) {
                $table->string('uid')->nullable()->after('type');
            }
            if (!Schema::hasColumn('user_socials', 'email')) {
                $table->string('email')->nullable()->after('uid');
            }
            if (!Schema::hasColumn('user_socials', 'login')) {
                $table->string('login')->nullable()->after('email');
            }
            if (!Schema::hasColumn('user_socials', 'gender')) {
                $table->unsignedTinyInteger('gender')->nullable()->after('login');
            }
            if (!Schema::hasColumn('user_socials', 'name')) {
                $table->string('name')->nullable()->after('gender');
            }
            if (!Schema::hasColumn('user_socials', 'surname')) {
                $table->string('surname')->nullable()->after('name');
            }
            if (!Schema::hasColumn('user_socials', 'birthday')) {
                $table->date('birthday')->nullable()->after('surname');
            }
            if (!Schema::hasColumn('user_socials', 'avatar')) {
                $table->string('avatar')->nullable()->after('birthday');
            }
            if (!Schema::hasColumn('user_socials', 'link')) {
                $table->string('link')->nullable()->after('avatar');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (!Schema::hasTable('user_socials')) {
            return;
        }
    }
};
