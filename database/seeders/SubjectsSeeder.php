<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SubjectsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $level = DB::table('subscription_levels')->where('title', '1 класс')->first();
        if (!$level) {
            $levelId = DB::table('subscription_levels')->insertGetId([
                'title' => '1 класс',
                'link' => '/subjects/1',
                'sort_order' => 1,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        } else {
            $levelId = $level->id;
        }

        $subjects = [
            ['title' => 'Русский язык. Азбука', 'rating' => 120, 'link' => null],
            ['title' => 'Русский язык', 'rating' => 110, 'link' => '/demo/sub_2.html'],
            ['title' => 'Прописи (Горецкого В.Г.)', 'rating' => 100, 'link' => '/demo/sub_2.html'],
            ['title' => 'Чудо-прописи (Илюхиной В.А.)', 'rating' => 90, 'link' => '/demo/sub_2.html'],
            ['title' => 'Математика', 'rating' => 80, 'link' => '/demo/sub_2.html'],
            ['title' => 'Литературное чтение', 'rating' => 70, 'link' => '/demo/sub_2.html'],
            ['title' => 'Литературное чтение (родной русский)', 'rating' => 60, 'link' => '/demo/sub_2.html'],
            ['title' => 'Окружающий мир', 'rating' => 50, 'link' => '/demo/sub_2.html'],
            ['title' => 'Изобразительное искусство', 'rating' => 40, 'link' => '/demo/sub_2.html'],
            ['title' => 'Труд (технология)', 'rating' => 30, 'link' => '/demo/sub_2.html'],
            ['title' => 'Музыка', 'rating' => 20, 'link' => '/demo/sub_2.html'],
            ['title' => 'Физическая культура', 'rating' => 10, 'link' => '/demo/sub_2.html'],
        ];

        foreach ($subjects as $subject) {
            DB::table('subjects')->updateOrInsert(
                ['title' => $subject['title']],
                [
                    'subscription_level_id' => $levelId,
                    'rating' => $subject['rating'],
                    'link' => $subject['link'],
                    'display' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );
        }

        $azbuka = DB::table('subjects')->where('title', 'Русский язык. Азбука')->first();
        if (!$azbuka) {
            return;
        }

        $topics = [
            [
                'title' => 'Здравствуй, школа!',
                'keywords' => 'школа осанка правила учебник знакомство история прописи',
            ],
            [
                'title' => 'Устная и письменная речь. Предложение',
                'keywords' => 'устная письменная речь предложение',
            ],
            [
                'title' => 'Кто любит трудиться, тому без дела не сидится. Предложение и слово',
                'keywords' => 'трудиться без дела предложение слово',
            ],
        ];

        foreach ($topics as $topic) {
            DB::table('topics')->updateOrInsert(
                [
                    'title' => $topic['title'],
                    'subscription_level_id' => $levelId,
                    'subject_id' => $azbuka->id,
                ],
                [
                    'keywords' => $topic['keywords'],
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );
        }

        $firstTopic = DB::table('topics')->where('title', 'Здравствуй, школа!')->first();
        if (!$firstTopic) {
            return;
        }

        $lessonText = '<div><b>Русский язык. Азбука. 1 класс.</b> Горецкий В.Г., Кирюшкин В.А. и др.</div>'
            . '<div>Издательство 2023 года (по ФОП)</div>'
            . '<div>Учебник, 1 часть, с. 4</div>'
            . '<hr style="border:none;border-top:1px solid #e2e2e2;margin:10px 0;">'
            . '<div><b>Структура урока:</b></div>'
            . '<ol style="margin:8px 0 0 18px;">'
            . '<li>Правила поведения. Осанка.</li>'
            . '<li>Знакомство с учебником</li>'
            . '<li>Немного истории</li>'
            . '<li>Письменная работа (прописи)</li>'
            . '<li>Работа по теме урока</li>'
            . '<li>Физминутка</li>'
            . '<li>Продолжение работы по теме урока</li>'
            . '<li>Письменная работа (прописи)</li>'
            . '<li>Рефлексия</li>'
            . '</ol>'
            . '<div style="margin-top:10px;"><b>Приложение к уроку:</b></div>'
            . '<ul><li>Творческое задание</li></ul>'
            . '<div style="margin-top:10px;"><b>Соответствие страниц учебника прописям:</b></div>'
            . '<div>Прописи № 1, с. 3–6.</div>';

        DB::table('topics')->where('id', $firstTopic->id)->update([
            'text' => $lessonText,
            'updated_at' => now(),
        ]);

        DB::table('topic_materials')->updateOrInsert(
            [
                'title' => 'Презентация',
                'subscription_level_id' => $levelId,
                'subject_id' => $azbuka->id,
                'topic_id' => $firstTopic->id,
            ],
            [
                'display' => true,
                'pdf_file' => '/demo/files/sub_1/RUS_A/1/presentation.pdf',
                'zip_file' => '/demo/files/sub_1/RUS_A/1/presentation.zip',
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );

        DB::table('topic_materials')->updateOrInsert(
            [
                'title' => 'Творческое задание',
                'subscription_level_id' => $levelId,
                'subject_id' => $azbuka->id,
                'topic_id' => $firstTopic->id,
            ],
            [
                'display' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );
    }
}
