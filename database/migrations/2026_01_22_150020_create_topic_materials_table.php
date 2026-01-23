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
        Schema::create('topic_materials', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->boolean('is_blocked')->default(false);
            $table->boolean('display')->default(true);
            $table->unsignedBigInteger('subscription_level_id');
            $table->unsignedBigInteger('subject_id');
            $table->unsignedBigInteger('topic_id');
            $table->text('text')->nullable();
            $table->string('pdf_file')->nullable();
            $table->string('zip_file')->nullable();
            $table->timestamps();

            $table->foreign('subscription_level_id')
                ->references('id')
                ->on('subscription_levels')
                ->onDelete('cascade');

            $table->foreign('subject_id')
                ->references('id')
                ->on('subjects')
                ->onDelete('cascade');

            $table->foreign('topic_id')
                ->references('id')
                ->on('topics')
                ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('topic_materials');
    }
};
