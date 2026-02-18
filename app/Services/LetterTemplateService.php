<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;

/**
 * Отправка писем по шаблонам из letter_templates с подстановкой {{переменная}}.
 * Шаблоны хранятся в public/abc/files/letter_templates/{slug}/1/subject.tpl и body.tpl.
 * Поиск по полю slug (registration_confirm, payment_success, ...).
 */
class LetterTemplateService
{
    private string $templatesPath;

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
        $row = DB::table('letter_templates')->where('slug', $templateSlug)->first();
        if (! $row) {
            return false;
        }

        $pathBase = $row->slug ?: $row->name;
        $dir = $this->templatesPath . '/' . $pathBase . '/' . $languageId;
        $subjectFile = $dir . '/subject.tpl';
        $bodyFile = $dir . '/body.tpl';

        if (! is_file($subjectFile) || ! is_file($bodyFile)) {
            return false;
        }

        $subject = $this->substitute(file_get_contents($subjectFile), $variables, false);
        $body = $this->substitute(file_get_contents($bodyFile), $variables, true);

        $senderEmail = ! empty($row->sender) ? $row->sender : config('mail.from.address');
        $senderName = ! empty($row->sender_name) ? $row->sender_name : config('mail.from.name', config('app.name'));

        $letter = [
            'date' => now()->format('Y-m-d H:i:s'),
            'date_sent' => now()->format('Y-m-d H:i:s'),
            'sender' => $senderEmail,
            'sender_name' => $senderName,
            'receiver' => $receiver,
            'subject' => $subject,
            'text' => $body,
        ];
        DB::table('letters')->insert($letter);

        try {
            Mail::html($body, function ($message) use ($receiver, $subject, $senderEmail, $senderName) {
                $message->to($receiver)
                    ->subject($subject)
                    ->from($senderEmail, $senderName);
            });
            return true;
        } catch (\Throwable $e) {
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
