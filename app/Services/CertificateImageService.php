<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Log;

/**
 * Наложение ФИО (или email/phone/user_code) на изображение сертификата.
 * Три отдельных текстовых блока: фамилия, имя, отчество. Параметры — в config/portfolio.php.
 */
class CertificateImageService
{
    private const MAX_LINES = 3;

    private string $fontPath;

    public function __construct()
    {
        $customPath = config('portfolio.certificate.certificate_font_path');
        $this->fontPath = ($customPath !== null && $customPath !== '' && is_file($customPath))
            ? $customPath
            : $this->resolveFontPath();
    }

    /**
     * Путь к TTF-шрифту с поддержкой кириллицы (обязательно для корректного отображения).
     */
    private function resolveFontPath(): string
    {
        $candidates = [
            config('portfolio.certificate.certificate_font_path'),
            public_path('fonts/DejaVuSans.ttf'),
            public_path('fonts/arial.ttf'),
            storage_path('app/fonts/DejaVuSans.ttf'),
            storage_path('app/fonts/arial.ttf'),
        ];
        $windir = getenv('WINDIR');
        if ($windir !== false && $windir !== '') {
            $candidates[] = $windir . DIRECTORY_SEPARATOR . 'Fonts' . DIRECTORY_SEPARATOR . 'arial.ttf';
            $candidates[] = $windir . DIRECTORY_SEPARATOR . 'Fonts' . DIRECTORY_SEPARATOR . 'Arial.ttf';
        }
        $candidates[] = 'C:\\Windows\\Fonts\\arial.ttf';
        $candidates[] = '/usr/share/fonts/truetype/dejavu/DejaVuSans.ttf';
        $candidates[] = '/usr/share/fonts/truetype/liberation/LiberationSans-Regular.ttf';

        foreach ($candidates as $path) {
            if ($path !== null && $path !== '' && is_file($path)) {
                return $path;
            }
        }

        return public_path('fonts/DejaVuSans.ttf');
    }

    /**
     * Три строки для сертификата: 1 — фамилия, 2 — имя, 3 — отчество.
     * Если ФИО пустое, подставляется fallback (email/phone/user_code), разбитый на до 3 строк.
     *
     * @return array<int, string>
     */
    public function linesForUser(User $user): array
    {
        $surname = trim((string) ($user->last_name ?? ''));
        $name = trim((string) ($user->first_name ?? ''));
        $patronymic = trim((string) ($user->middle_name ?? ''));

        if ($surname !== '' || $name !== '' || $patronymic !== '') {
            return [
                $surname,
                $name,
                $patronymic,
            ];
        }

        $fallback = trim((string) ($user->email ?? ''));
        if ($fallback === '') {
            $fallback = trim((string) ($user->phone ?? ''));
        }
        if ($fallback === '') {
            $fallback = (string) ($user->user_code ?? (string) $user->id);
        }

        return $this->wrapToLines($fallback, self::MAX_LINES);
    }

    /**
     * Создать изображение с наложенным ФИО (фамилия, имя, отчество по строкам) и вернуть путь к временному файлу.
     * Исходный файл не изменяется.
     *
     * @param  string  $sourcePath  Полный путь к PNG/JPEG сертификата
     * @param  User  $user  Пользователь (ФИО или fallback)
     * @return string Путь к временному файлу
     */
    public function createWithText(string $sourcePath, User $user): string
    {
        $lines = $this->linesForUser($user);
        Log::channel('single')->info('CertificateImageService: createWithText start', [
            'sourcePath' => $sourcePath,
            'sourceExists' => is_file($sourcePath),
            'lines' => $lines,
            'storage_app' => storage_path('app'),
            'storage_app_writable' => is_writable(storage_path('app')),
        ]);

        $image = $this->loadImage($sourcePath);
        if (!$image) {
            Log::channel('single')->error('CertificateImageService: loadImage failed', ['path' => $sourcePath]);
            throw new \RuntimeException('Не удалось загрузить изображение сертификата.');
        }

        $width = imagesx($image);
        $height = imagesy($image);

        $centerX = (float) config('portfolio.certificate.center_x', 0.69);
        $blockTop = (float) config('portfolio.certificate.block_top', 0.14);
        $lineSpacing = (float) config('portfolio.certificate.line_spacing', 0.028);
        $fontSize = (int) config('portfolio.certificate.font_size', 28);

        $this->drawThreeBlocks($image, $lines, $width, $height, $centerX, $blockTop, $lineSpacing, $fontSize);

        $ext = strtolower(pathinfo($sourcePath, PATHINFO_EXTENSION)) ?: 'png';
        $dir = storage_path('app');
        if (!is_dir($dir)) {
            $mkdir = @mkdir($dir, 0755, true);
            Log::channel('single')->info('CertificateImageService: mkdir storage/app', ['ok' => $mkdir]);
        }
        $tmpPath = $dir . '/certificate_' . uniqid('', true) . '.' . $ext;

        $this->saveImage($image, $tmpPath, $ext);
        imagedestroy($image);

        $exists = is_file($tmpPath);
        $size = $exists ? filesize($tmpPath) : 0;
        Log::channel('single')->info('CertificateImageService: createWithText done', [
            'tmpPath' => $tmpPath,
            'exists' => $exists,
            'size' => $size,
        ]);

        return $tmpPath;
    }

    private function loadImage(string $path)
    {
        if (!is_file($path)) {
            return null;
        }
        $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
        if ($ext === 'png') {
            return @imagecreatefrompng($path);
        }
        if (in_array($ext, ['jpg', 'jpeg'], true)) {
            return @imagecreatefromjpeg($path);
        }
        return @imagecreatefrompng($path) ?: @imagecreatefromjpeg($path);
    }

    private function saveImage($image, string $path, string $ext): void
    {
        if ($ext === 'png') {
            $ok = imagepng($image, $path);
            Log::channel('single')->info('CertificateImageService: imagepng', ['path' => $path, 'ok' => $ok]);
        } else {
            $ok = imagejpeg($image, $path, 95);
            Log::channel('single')->info('CertificateImageService: imagejpeg', ['path' => $path, 'ok' => $ok]);
        }
    }

    /**
     * Разбить текст на не более MAX_LINES строк (по словам, примерно поровну).
     *
     * @return array<int, string>
     */
    private function wrapToLines(string $text, int $maxLines): array
    {
        $text = trim(preg_replace('/\s+/u', ' ', $text));
        if ($text === '') {
            return [''];
        }

        $words = explode(' ', $text);
        if (count($words) <= $maxLines) {
            return array_pad($words, $maxLines, '');
        }

        $lines = [];
        $perLine = (int) ceil(count($words) / $maxLines);
        $offset = 0;
        for ($i = 0; $i < $maxLines && $offset < count($words); $i++) {
            $slice = array_slice($words, $offset, $perLine);
            $lines[] = implode(' ', $slice);
            $offset += count($slice);
        }
        return array_pad($lines, $maxLines, '');
    }

    /**
     * Рисует 3 отдельных текстовых блока (фамилия, имя, отчество).
     * Каждый блок центрируется по горизонтали в одной точке centerX; вертикально — block_top + i * line_spacing.
     *
     * @param  resource  $image
     * @param  array<int, string>  $lines  Ровно 3 строки
     * @param  int  $width  Ширина изображения
     * @param  int  $height  Высота изображения
     * @param  float  $centerX  Центр по горизонтали (доля 0..1)
     * @param  float  $blockTop  Базовая линия 1-й строки (доля от высоты)
     * @param  float  $lineSpacing  Расстояние между строками (доля от высоты)
     * @param  int  $fontSize  Размер шрифта в пунктах
     */
    private function drawThreeBlocks($image, array $lines, int $width, int $height, float $centerX, float $blockTop, float $lineSpacing, int $fontSize): void
    {
        $color = imagecolorallocate($image, 0, 0, 80);
        $hasTtf = is_file($this->fontPath);

        if (!$hasTtf) {
            Log::channel('single')->warning('CertificateImageService: TTF font not found, Cyrillic may be broken', ['path' => $this->fontPath]);
        }

        $centerXPx = (int) round($width * $centerX);

        foreach (array_slice($lines, 0, self::MAX_LINES) as $i => $line) {
            $line = trim($line ?? '');
            $lineY = (int) round($height * ($blockTop + $i * $lineSpacing));

            if ($line === '') {
                continue;
            }

            if ($hasTtf) {
                $bbox = @imagettfbbox($fontSize, 0, $this->fontPath, $line);
                if ($bbox !== false) {
                    $textW = abs($bbox[4] - $bbox[0]);
                    $x = $centerXPx - (int) round($textW / 2);
                    imagettftext($image, $fontSize, 0, $x, $lineY, $color, $this->fontPath, $line);
                }
            } else {
                $textW = strlen($line) * imagefontwidth(5);
                $x = $centerXPx - (int) round($textW / 2);
                imagestring($image, 5, $x, $lineY - imagefontheight(5), $line, $color);
            }
        }
    }
}
