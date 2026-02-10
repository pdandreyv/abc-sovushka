<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('discount_codes', function (Blueprint $table) {
            $table->id();
            $table->string('code', 64)->unique();
            $table->date('valid_until')->comment('Действует до этой даты включительно');
            $table->unsignedInteger('usage_limit')->default(1)->comment('Максимум использований кода');
            $table->string('subscription_level_ids')->nullable()->comment('ID уровней подписок через запятую');
            $table->unsignedTinyInteger('discount_percent')->default(0)->comment('Процент скидки 0-100');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('discount_codes');
    }
};
