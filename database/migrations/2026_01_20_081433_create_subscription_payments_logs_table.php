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
        Schema::create('subscription_payments_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('subscription_order_id')->constrained('subscription_orders')->onDelete('cascade');
            $table->string('status'); // success, error, pending
            $table->decimal('amount', 10, 2); // Сумма платежа
            $table->text('message')->nullable(); // Сообщение об ошибке или успехе
            $table->text('response_data')->nullable(); // JSON ответ от платежной системы
            $table->string('payment_provider')->nullable(); // Провайдер платежа
            $table->string('transaction_id')->nullable(); // ID транзакции
            $table->timestamp('attempted_at'); // Время попытки списания
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('subscription_payments_logs');
    }
};
