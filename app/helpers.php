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
