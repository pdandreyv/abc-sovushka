<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_activity_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedMediumInteger('user_id');
            $table->string('ip', 45)->nullable();
            $table->unsignedTinyInteger('action'); // 1=вход, 12=скачивание материала, 13=просмотр материала
            $table->unsignedBigInteger('topic_material_id')->nullable();
            $table->timestamps();
        });

        Schema::table('user_activity_logs', function (Blueprint $table) {
            $table->index(['user_id', 'action', 'created_at']);
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_activity_logs');
    }
};
