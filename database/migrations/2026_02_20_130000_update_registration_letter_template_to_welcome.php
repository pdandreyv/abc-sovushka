<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private function templatesDir(): string
    {
        return base_path('public/abc/files/letter_templates/registration_confirm/1');
    }

    private function writeTemplateFiles(): void
    {
        $dir = $this->templatesDir();
        if (! is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        $subject = "Поздравляем с регистрацией — Совушкина школа\n";
        file_put_contents($dir . '/subject.tpl', $subject);

        $body = <<<'HTML'
<!doctype html>
<html lang="ru">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Поздравляем с регистрацией</title>
</head>
<body style="margin:0;padding:0;background:#F6F7FB;">
  <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="background:#F6F7FB;">
    <tr>
      <td align="center" style="padding:24px 12px;">
        <table role="presentation" width="600" cellspacing="0" cellpadding="0" border="0" style="width:600px;max-width:600px;">
          <tr>
            <td style="background:#FFFFFF;border-radius:16px;padding:22px 22px 18px 22px;border:1px solid #E7EAF0;">
              <div style="font-family:Arial,Helvetica,sans-serif;color:#1F2937;">
                <div style="font-size:18px;font-weight:700;line-height:1.3;margin:0 0 8px 0;">
                  Поздравляем с регистрацией!
                </div>
                <div style="font-size:14px;line-height:1.6;margin:0 0 14px 0;color:#374151;">
                  Здравствуйте{{#if user_name}}, {{user_name}}{{/if}}!<br>
                  Вы успешно зарегистрировались на сайте <b>Совушкина школа</b>. Сохраните данные для входа в личный кабинет:
                </div>
                <div style="font-size:14px;line-height:1.6;margin:14px 0;color:#374151;">
                  <strong>Логин:</strong> {{login}}<br>
                  <strong>Пароль:</strong> {{password}}
                </div>
                <div style="font-size:12px;line-height:1.6;margin:14px 0 0 0;color:#6B7280;">
                  Рекомендуем сменить пароль в профиле после первого входа. Если вы не регистрировались, обратитесь в поддержку.
                </div>
              </div>
            </td>
          </tr>
          <tr>
            <td style="padding:14px 6px 0 6px;">
              <div style="font-family:Arial,Helvetica,sans-serif;font-size:12px;line-height:1.6;color:#6B7280;">© {{year}} «Совушкина школа»</div>
            </td>
          </tr>
        </table>
      </td>
    </tr>
  </table>
</body>
</html>
HTML;
        file_put_contents($dir . '/body.tpl', $body);
    }

    public function up(): void
    {
        if (! Schema::hasTable('letter_templates')) {
            return;
        }

        DB::table('letter_templates')
            ->where('slug', 'registration_confirm')
            ->update([
                'name'        => 'Поздравляем с регистрацией — Совушкина школа',
                'h1'          => 'Поздравляем с регистрацией',
                'description' => 'Письмо после регистрации: логин и пароль для входа в личный кабинет.',
                'updated_at'  => now()->format('Y-m-d H:i:s'),
            ]);

        $this->writeTemplateFiles();
    }

    public function down(): void
    {
        if (! Schema::hasTable('letter_templates')) {
            return;
        }

        DB::table('letter_templates')
            ->where('slug', 'registration_confirm')
            ->update([
                'name'        => 'Подтвердите регистрацию в «Совушкина школа»',
                'h1'          => 'Подтверждение регистрации',
                'description' => 'Письмо после регистрации: ссылка и код подтверждения email.',
                'updated_at'  => now()->format('Y-m-d H:i:s'),
            ]);

        // Файлы шаблона в down() не восстанавливаем — при откате останутся новые
    }
};
