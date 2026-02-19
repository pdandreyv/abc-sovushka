<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

class SendTestMail extends Command
{
    protected $signature = 'mail:test {email : Email получателя}';
    protected $description = 'Отправить тестовое письмо для проверки настройки почты на сервере';

    public function handle(): int
    {
        $email = $this->argument('email');

        if (! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->error('Укажите корректный email.');
            return self::FAILURE;
        }

        $subject = 'Тестовое письмо — ' . config('app.name');
        $body = $this->getTestBody();

        try {
            Mail::html($body, function ($message) use ($email, $subject) {
                $message->to($email)
                    ->subject($subject)
                    ->from(config('mail.from.address'), config('mail.from.name'));
            });
            $this->info("Тестовое письмо отправлено на {$email}");
            $this->comment('Если MAIL_MAILER=log — письмо записано в storage/logs/laravel.log');
            return self::SUCCESS;
        } catch (\Throwable $e) {
            $this->error('Ошибка отправки: ' . $e->getMessage());
            report($e);
            return self::FAILURE;
        }
    }

    private function getTestBody(): string
    {
        $time = now()->format('d.m.Y H:i:s');
        $driver = config('mail.default');

        return <<<HTML
<!DOCTYPE html>
<html>
<head><meta charset="utf-8"><title>Тест</title></head>
<body style="font-family: sans-serif; padding: 20px;">
  <h2>Тестовое письмо</h2>
  <p>Это письмо отправлено с сервера для проверки настройки почты.</p>
  <p><strong>Время:</strong> {$time}</p>
  <p><strong>Приложение:</strong> {$this->laravel->environment()}</p>
  <p><strong>Mail driver:</strong> {$driver}</p>
  <hr>
  <p style="color:#666; font-size:12px;">ABC Sovushka — тест отправки писем</p>
</body>
</html>
HTML;
    }
}
