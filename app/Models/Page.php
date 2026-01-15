<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Page extends Model
{
    /**
     * Имя таблицы
     */
    protected $table = 'pages';

    /**
     * Поля, которые можно массово заполнять
     */
    protected $fillable = [
        'language',
        'parent',
        'left_key',
        'right_key',
        'level',
        'display',
        'menu',
        'menu2',
        'noindex',
        'module',
        'name',
        'h1',
        'url',
        'title',
        'description',
        'hypertext',
        'text',
        'created_at',
        'updated_at',
    ];

    /**
     * Типы данных для полей
     */
    protected $casts = [
        'display' => 'boolean',
        'menu' => 'boolean',
        'menu2' => 'boolean',
        'noindex' => 'boolean',
        'language' => 'integer',
        'parent' => 'integer',
        'left_key' => 'integer',
        'right_key' => 'integer',
        'level' => 'integer',
    ];

    /**
     * Получить страницу по URL
     */
    public static function findByUrl($url, $language = 1)
    {
        return static::where('url', $url)
            ->where('language', $language)
            ->where('display', 1)
            ->first();
    }

    /**
     * Обработать поле hypertext и вернуть HTML контент
     * Извлекает HTML контент из JSON структуры hypertext
     */
    public function getHypertextContentAttribute()
    {
        if (empty($this->hypertext)) {
            return '';
        }

        $data = json_decode($this->hypertext, true);
        if (!$data || !is_array($data)) {
            return '';
        }

        $content = '';
        foreach ($data as $item) {
            if (!isset($item['type'])) {
                continue;
            }

            // Извлекаем HTML контент
            if ($item['type'] === 'html' && isset($item['content'])) {
                $content .= $item['content'];
            }
            // Для других типов (img, video, images) можно добавить обработку при необходимости
        }

        return $content;
    }
}
