<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class SubscriptionLevelsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        \DB::table('subscription_levels')->insert([
            ['title' => '1 класс', 'slug' => 'grade1', 'link' => 'sub_1.html', 'sort_order' => 1, 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
            ['title' => '2 класс', 'slug' => 'grade2', 'link' => 'sub_2.html', 'sort_order' => 2, 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
            ['title' => '3 класс', 'slug' => 'grade3', 'link' => 'sub_3.html', 'sort_order' => 3, 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
            ['title' => '4 класс', 'slug' => 'grade4', 'link' => 'sub_4.html', 'sort_order' => 4, 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
            ['title' => 'Дошкольники', 'slug' => 'pre', 'link' => 'sub_preschool.html', 'sort_order' => 5, 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
            ['title' => 'Внеурочная деятельность', 'slug' => 'extra', 'link' => 'sub_extracurricular.html', 'sort_order' => 6, 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
            ['title' => 'Обучение', 'slug' => 'train', 'link' => 'sub_training.html', 'sort_order' => 7, 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
        ]);
    }
}
