#!/usr/bin/env php
<?php
/**
 * Регенерация превью 270x185 для Кладовой идей из оригинальных изображений.
 * Запуск из корня проекта: php public/abc/regenerate_ideas_previews.php
 * Или из public/abc: php regenerate_ideas_previews.php
 *
 * Берёт оригиналы из public/abc/files/ideas/{id}/image/ или public/files/ideas/{id}/image/
 * и пересоздаёт превью в public/abc/_imgs/270x185/files/ideas/{id}/image/
 * с качеством 95 для чёткого отображения в ЛК.
 */

$isCli = (php_sapi_name() === 'cli');
if (!$isCli) {
    die('Скрипт предназначен только для запуска из командной строки.');
}

// Для подключаемых конфигов, ожидающих веб-окружение
if (!isset($_SERVER['HTTP_HOST'])) {
    $_SERVER['HTTP_HOST'] = 'localhost';
}
if (!isset($_SERVER['REMOTE_ADDR'])) {
    $_SERVER['REMOTE_ADDR'] = '127.0.0.1';
}
if (!isset($_SERVER['SERVER_ADDR'])) {
    $_SERVER['SERVER_ADDR'] = '127.0.0.1';
}
if (!isset($_SERVER['HTTP_ACCEPT'])) {
    $_SERVER['HTTP_ACCEPT'] = '';
}

$selfDir = dirname(__FILE__);
chdir($selfDir);
define('ROOT_DIR', $selfDir . '/');

require_once ROOT_DIR . '_config2.php';
require_once ROOT_DIR . 'functions/common_func.php';
require_once ROOT_DIR . 'functions/image_func.php';

$previewSize = '270x185';
$quality = 95; // выше качество — менее размытое превью
$type = 'resize';

// Возможные корни для оригиналов (как в _imgs/index.php)
$abcFiles = ROOT_DIR . 'files/ideas/';
$publicFiles = ROOT_DIR . '../files/ideas/';

$ideaIds = [];
foreach ([$abcFiles, $publicFiles] as $base) {
    if (!is_dir($base)) {
        continue;
    }
    $dirs = glob($base . '*', GLOB_ONLYDIR);
    foreach ($dirs as $path) {
        $id = basename($path);
        if (is_numeric($id) && $id > 0 && !in_array($id, $ideaIds)) {
            $ideaIds[] = $id;
        }
    }
}
sort($ideaIds, SORT_NUMERIC);

if (empty($ideaIds)) {
    echo "Папок с идеями не найдено в files/ideas/.\n";
    exit(0);
}

echo "Найдено идей: " . count($ideaIds) . " (id: " . implode(', ', $ideaIds) . ")\n";
echo "Размер превью: {$previewSize}, качество JPEG: {$quality}\n\n";

$done = 0;
$errors = 0;

foreach ($ideaIds as $id) {
    $imageDirAbc = $abcFiles . $id . '/image/';
    $imageDirPublic = $publicFiles . $id . '/image/';

    $imageDir = is_dir($imageDirPublic) ? $imageDirPublic : (is_dir($imageDirAbc) ? $imageDirAbc : null);
    if (!$imageDir) {
        echo "[id={$id}] папка image/ не найдена — пропуск\n";
        continue;
    }

    $files = glob($imageDir . '*');
    foreach ($files as $path) {
        if (!is_file($path)) {
            continue;
        }
        $filename = basename($path);
        // Пропускаем уже сгенерированные превью (артефакты copy2: 270x185имя.png)
        if (strpos($filename, $previewSize) === 0) {
            continue;
        }
        $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
        if (!in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp'], true)) {
            continue;
        }
        $dstDir = ROOT_DIR . '_imgs/' . $previewSize . '/files/ideas/' . $id . '/image/';
        $dstPath = $dstDir . $filename;

        if (!is_dir($dstDir) && !mkdir($dstDir, 0755, true)) {
            echo "[id={$id}] {$filename}: не удалось создать папку превью\n";
            $errors++;
            continue;
        }

        $ok = img_process($type, $path, $previewSize, $dstPath, $quality);
        if ($ok) {
            echo "[id={$id}] {$filename} -> превью создано\n";
            $done++;
        } else {
            echo "[id={$id}] {$filename}: ошибка генерации превью\n";
            $errors++;
        }
    }
}

echo "\nГотово. Успешно: {$done}, ошибок: {$errors}\n";
