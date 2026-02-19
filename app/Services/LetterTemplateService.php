<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

/**
 * Отправка писем по шаблонам из letter_templates с подстановкой {{переменная}}.
 * Шаблоны хранятся в public/abc/files/letter_templates/{slug}/1/subject.tpl и body.tpl.
 * Поиск по полю slug (registration_confirm, payment_success, ...).
 */
class LetterTemplateService
{
    private string $templatesPath;

    /** Соответствие slug -> начало name для поиска в БД, если slug пустой (старая админка). */
    private const SLUG_TO_NAME_PREFIX = [
        'registration_confirm' => 'Подтвердите регистрацию',
        'payment_success' => 'Оплата получена',
        'charge_success_renewed' => 'Подписка продлена',
        'charge_failed_attempts_left' => 'Не удалось списать',
        'access_ended_after_cancel' => 'Доступ завершён',
        'access_suspended_after_3_attempts' => 'Доступ приостановлен',
    ];

    public function __construct()
    {
        $this->templatesPath = base_path('public/abc/files/letter_templates');
    }

    /**
     * Отправить письмо по шаблону.
     *
     * @param string $templateSlug slug шаблона: registration_confirm, payment_success, ...
     * @param string $receiver email получателя
     * @param array<string, mixed> $variables переменные для подстановки в {{key}}
     * @param int $languageId ID языка (по умолчанию 1)
     * @return bool отправлено ли письмо
     */
    public function send(string $templateSlug, string $receiver, array $variables = [], int $languageId = 1): bool
    {
        $regLog = Log::channel('registration');
        $ctx = ['slug' => $templateSlug, 'receiver' => $receiver];

        if ($templateSlug === 'registration_confirm') {
            $regLog->info('LetterTemplateService: начало отправки', $ctx);
        }

        $row = DB::table('letter_templates')->where('slug', $templateSlug)->first();
        $pathBase = $templateSlug;

        if ($row) {
            $pathBase = ! empty($row->slug) ? $row->slug : $templateSlug;
        } else {
            $prefix = self::SLUG_TO_NAME_PREFIX[$templateSlug] ?? null;
            if ($prefix !== null) {
                $row = DB::table('letter_templates')->where('name', 'like', $prefix . '%')->first();
                if ($row) {
                    $pathBase = $templateSlug;
                }
            }
        }

        if (! $row) {
            $msg = 'LetterTemplateService: шаблон не найден в letter_templates';
            Log::warning($msg, $ctx);
            if ($templateSlug === 'registration_confirm') {
                $regLog->warning($msg, $ctx);
            }
            return false;
        }

        $dir = $this->templatesPath . '/' . $pathBase . '/' . $languageId;
        $subjectFile = $dir . '/subject.tpl';
        $bodyFile = $dir . '/body.tpl';

        if (! is_file($subjectFile) || ! is_file($bodyFile)) {
            $msg = 'LetterTemplateService: файлы шаблона не найдены';
            $fileCtx = $ctx + ['path_base' => $pathBase, 'subject_file' => $subjectFile, 'body_file' => $bodyFile];
            Log::warning($msg, $fileCtx);
            if ($templateSlug === 'registration_confirm') {
                $regLog->warning($msg, $fileCtx);
            }
            return false;
        }

        $subject = $this->substitute(file_get_contents($subjectFile), $variables, false);
        $body = $this->substitute(file_get_contents($bodyFile), $variables, true);

        $senderEmail = ! empty($row->sender) ? $row->sender : config('mail.from.address');
        $senderName = ! empty($row->sender_name) ? $row->sender_name : config('mail.from.name', config('app.name'));

        $now = now()->format('Y-m-d H:i:s');
        $letter = [
            'date' => $now,
            'date_sent' => $now,
            'sender' => $senderEmail,
            'sender_name' => $senderName,
            'receiver' => $receiver,
            'subject' => $subject,
            'text' => $body,
            'created_at' => $now,
            'updated_at' => $now,
        ];

        try {
            DB::table('letters')->insert($letter);
            if ($templateSlug === 'registration_confirm') {
                $regLog->info('LetterTemplateService: запись в letters создана');
            }
        } catch (\Throwable $e) {
            $errCtx = $ctx + ['message' => $e->getMessage()];
            Log::error('LetterTemplateService: ошибка вставки в letters', $errCtx);
            if ($templateSlug === 'registration_confirm') {
                $regLog->error('LetterTemplateService: ошибка вставки в letters', $errCtx);
            }
            report($e);
            return false;
        }

        try {
            Mail::html($body, function ($message) use ($receiver, $subject, $senderEmail, $senderName) {
                $message->to($receiver)
                    ->subject($subject)
                    ->from($senderEmail, $senderName);
            });
            if ($templateSlug === 'registration_confirm') {
                $regLog->info('LetterTemplateService: Mail::html выполнен успешно', $ctx);
            }
            return true;
        } catch (\Throwable $e) {
            $errCtx = $ctx + ['message' => $e->getMessage()];
            Log::error('LetterTemplateService: ошибка отправки почты', $errCtx);
            if ($templateSlug === 'registration_confirm') {
                $regLog->error('LetterTemplateService: ошибка отправки почты', $errCtx);
            }
            report($e);
            return false;
        }
    }

    /**
     * Подстановка переменных в текст: {{key}} -> value.
     * Для body: дополнительно обрабатывается {{#if user_name}}...{{/if}}.
     */
    private function substitute(string $text, array $variables, bool $isBody): string
    {
        foreach ($variables as $k => $v) {
            $text = str_replace('{{' . $k . '}}', (string) $v, $text);
        }
        if ($isBody) {
            $hasUserName = isset($variables['user_name']) && (string) $variables['user_name'] !== '';
            $text = preg_replace(
                '/\{\{#if\s+user_name\}\}(.*?)\{\{\/if\}\}/s',
                $hasUserName ? '$1' : '',
                $text
            );
        }
        return $text;
    }
}
