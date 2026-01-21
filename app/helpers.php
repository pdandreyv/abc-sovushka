<?php

if (!function_exists('asset_versioned')) {
    /**
     * Генерирует URL для asset с версией на основе времени модификации файла
     * 
     * @param string $path Путь к файлу относительно public
     * @return string URL с версией
     */
    function asset_versioned(string $path): string
    {
        $filePath = public_path($path);
        
        if (file_exists($filePath)) {
            $version = filemtime($filePath);
            return asset($path) . '?v=' . $version;
        }
        
        return asset($path);
    }
}

if (!function_exists('site_lang')) {
    /**
     * Получает строку из словаря админки.
     *
     * Формат ключа: "group|key"
     */
    function site_lang(string $key, ?string $default = null): string
    {
        static $dictionary = null;

        if ($dictionary === null) {
            $dictionary = [];
            $dir = public_path('abc/files/languages/1/dictionary');
            if (is_dir($dir)) {
                foreach (glob($dir . '/*.php') as $file) {
                    $lang = [];
                    include $file;
                    if (!empty($lang) && is_array($lang)) {
                        foreach ($lang as $group => $items) {
                            if (!is_array($items)) {
                                continue;
                            }
                            if (!isset($dictionary[$group])) {
                                $dictionary[$group] = [];
                            }
                            $dictionary[$group] = array_merge($dictionary[$group], $items);
                        }
                    }
                }
            }
        }

        $parts = explode('|', $key, 2);
        $group = $parts[0] ?? null;
        $item = $parts[1] ?? null;

        if ($group && $item && isset($dictionary[$group][$item])) {
            return $dictionary[$group][$item];
        }

        return $default ?? $key;
    }
}
