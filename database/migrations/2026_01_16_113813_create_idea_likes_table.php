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
        Schema::create('idea_likes', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('idea_id');
            $table->unsignedBigInteger('user_id');
            $table->timestamps();
            
            // Индексы для быстрого поиска
            $table->index('idea_id');
            $table->index('user_id');
            
            // Уникальный индекс, чтобы один пользователь мог лайкнуть идею только один раз
            $table->unique(['idea_id', 'user_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('idea_likes');
    }
};
