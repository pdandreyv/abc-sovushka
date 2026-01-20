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
        Schema::create('subscription_tariffs', function (Blueprint $table) {
            $table->id();
            $table->string('title'); // Название тарифа (1 месяц, 3 месяца и т.д.)
            $table->decimal('price', 10, 2); // Цена
            $table->integer('days'); // Количество дней (1 месяц = 30, 3 месяца = 91, 12 месяцев = 365)
            $table->integer('rating')->default(0); // Рейтинг
            $table->boolean('is_visible')->default(true); // Показывать тариф
            $table->integer('sort_order')->default(0); // Порядок сортировки
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('subscription_tariffs');
    }
};
