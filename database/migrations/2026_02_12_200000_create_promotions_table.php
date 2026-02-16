<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('promotions')) {
            Schema::create('promotions', function (Blueprint $table) {
            $table->id();
            $table->unsignedMediumInteger('user_id')->comment('Пользователь');
            $table->string('subscription_level_ids')->comment('ID уровней подписок через запятую');
            $table->unsignedInteger('tariff_id')->comment('Тариф');
            $table->decimal('special_price', 10, 2)->comment('Специальная цена за тариф');
            $table->unsignedInteger('free_days')->default(0)->comment('Бесплатных дней');
            $table->boolean('used')->default(false)->comment('Использовано');
            $table->timestamp('used_at')->nullable()->comment('Когда использовано');
            $table->timestamps();

            $table->index('user_id');
            $table->index('tariff_id');
        });
        }

        if (Schema::hasTable('subscription_orders') && !Schema::hasColumn('subscription_orders', 'promotion_id')) {
            Schema::table('subscription_orders', function (Blueprint $table) {
                $table->unsignedBigInteger('promotion_id')->nullable()->after('id')->comment('Акция (если заказ по акции)');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('subscription_orders', 'promotion_id')) {
            Schema::table('subscription_orders', function (Blueprint $table) {
                $table->dropColumn('promotion_id');
            });
        }
        Schema::dropIfExists('promotions');
    }
};
