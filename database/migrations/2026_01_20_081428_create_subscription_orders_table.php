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
        Schema::create('subscription_orders', function (Blueprint $table) {
            $table->id();
            $table->unsignedMediumInteger('user_id');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->json('subscription_level_ids'); // Массив ID уровней подписок
            $table->date('date_subscription'); // Дата подписки
            $table->decimal('sum_subscription', 10, 2); // Сумма подписки
            $table->integer('days'); // Количество дней (берется из subscription_tariffs)
            $table->date('date_next_pay')->nullable(); // Дата следующего списания
            $table->decimal('sum_next_pay', 10, 2)->nullable(); // Сумма следующего платежа
            $table->string('hash')->nullable(); // Хеш карты для рекуррентного платежа
            $table->integer('errors')->default(0); // Количество попыток списания
            $table->boolean('auto')->default(false); // Флаг автоматического списания
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('subscription_orders');
    }
};
