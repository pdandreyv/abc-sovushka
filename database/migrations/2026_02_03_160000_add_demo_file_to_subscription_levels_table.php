<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Демо-файл уровня подписки (отображение и скачивание как на demo/sub_1.html).
     */
    public function up(): void
    {
        Schema::table('subscription_levels', function (Blueprint $table) {
            $table->string('demo_file', 255)->nullable()->after('link')->comment('Имя загруженного файла для демо/скачивания');
        });
    }

    public function down(): void
    {
        Schema::table('subscription_levels', function (Blueprint $table) {
            $table->dropColumn('demo_file');
        });
    }
};
