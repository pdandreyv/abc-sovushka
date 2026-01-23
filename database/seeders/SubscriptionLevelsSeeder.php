<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SubscriptionLevelsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $rows = [
            ['title' => '1 класс', 'link' => null, 'sort_order' => 1, 'is_active' => true],
            ['title' => '2 класс', 'link' => null, 'sort_order' => 2, 'is_active' => true],
            ['title' => '3 класс', 'link' => null, 'sort_order' => 3, 'is_active' => true],
            ['title' => '4 класс', 'link' => null, 'sort_order' => 4, 'is_active' => true],
            ['title' => 'Дошкольники', 'link' => null, 'sort_order' => 5, 'is_active' => true],
            ['title' => 'Внеурочная деятельность', 'link' => null, 'sort_order' => 6, 'is_active' => true],
            ['title' => 'Обучение', 'link' => null, 'sort_order' => 7, 'is_active' => true],
        ];

        foreach ($rows as $row) {
            DB::table('subscription_levels')->updateOrInsert(
                ['title' => $row['title']],
                array_merge($row, ['created_at' => now(), 'updated_at' => now()])
            );
        }

        $levels = DB::table('subscription_levels')->select('id')->orderBy('sort_order')->get();
        foreach ($levels as $level) {
            DB::table('subscription_levels')
                ->where('id', $level->id)
                ->update(['link' => '/subjects/' . $level->id]);
        }
    }
}
