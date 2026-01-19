<?php

namespace App\Http\Controllers;

use App\Models\Page;
use Illuminate\Http\Request;

class PageController extends Controller
{
    /**
     * Отобразить страницу по URL
     */
    public function show($url)
    {
        // Получаем страницу по URL
        $page = Page::findByUrl($url);

        // Если страница не найдена, возвращаем 404
        if (!$page) {
            abort(404, 'Страница не найдена');
        }

        // Обрабатываем контент из hypertext
        $content = $page->hypertext_content;
        
        // Если есть обычное поле text, используем его как fallback
        if (empty($content) && !empty($page->text)) {
            $content = $page->text;
        }

        return view('page.show', [
            'page' => $page,
            'content' => $content,
            'url' => $url,
        ]);
    }
}
