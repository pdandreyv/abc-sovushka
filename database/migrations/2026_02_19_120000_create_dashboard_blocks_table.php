<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Блоки для дашборда ЛК: баннеры (картинка + ссылка) и текстовые объявления.
     */
    public function up(): void
    {
        Schema::create('dashboard_blocks', function (Blueprint $table) {
            $table->id();
            $table->string('type', 20)->default('banner'); // banner | announcement
            $table->string('title')->nullable();
            $table->string('image')->nullable();
            $table->string('url', 1024)->nullable();
            $table->text('text')->nullable();
            $table->unsignedInteger('rank')->default(0);
            $table->boolean('display')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('dashboard_blocks');
    }
};
