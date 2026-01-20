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
        Schema::create('subscription_levels', function (Blueprint $table) {
            $table->id();
            $table->string('title'); // Название уровня (1 класс, 2 класс и т.д.)
            $table->string('slug')->unique(); // Уникальный идентификатор (grade1, grade2 и т.д.)
            $table->string('link')->nullable(); // Ссылка на страницу направления
            $table->integer('sort_order')->default(0); // Порядок сортировки
            $table->boolean('is_active')->default(true); // Активен ли уровень
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('subscription_levels');
    }
};
