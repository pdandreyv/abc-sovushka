<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (Schema::hasColumn('topic_materials', 'text') && Schema::hasColumn('topics', 'text')) {
            $rows = DB::table('topic_materials')
                ->select('topic_id', 'text')
                ->whereNotNull('text')
                ->orderBy('id')
                ->get();

            foreach ($rows as $row) {
                DB::table('topics')
                    ->where('id', $row->topic_id)
                    ->whereNull('text')
                    ->update(['text' => $row->text]);
            }
        }

        Schema::table('topic_materials', function (Blueprint $table) {
            if (Schema::hasColumn('topic_materials', 'text')) {
                $table->dropColumn('text');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('topic_materials', function (Blueprint $table) {
            $table->longText('text')->nullable()->after('topic_id');
        });
    }
};
