<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const SENDER_EMAIL = 'info@kssovushka.ru';
    private const SENDER_NAME = 'Совушкина школа';

    private function templatesBasePath(): string
    {
        return base_path('public/abc/files/letter_templates');
    }

    private function writeTemplateFiles(string $slug, string $subjectContent, string $bodyContent): void
    {
        $langId = 1;
        $dir = $this->templatesBasePath() . '/' . $slug . '/' . $langId;
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
        $substSubject = <<<'PHP'
<?php
$s = file_get_contents(__DIR__ . '/subject.tpl');
if (isset($q) && is_array($q)) { foreach ($q as $k => $v) { $s = str_replace('{{'.$k.'}}', (string)$v, $s); } }
echo $s;
PHP;
        $substBody = <<<'PHP'
<?php
$s = file_get_contents(__DIR__ . '/body.tpl');
if (isset($q) && is_array($q)) { foreach ($q as $k => $v) { $s = str_replace('{{'.$k.'}}', (string)$v, $s); } }
$s = preg_replace('/\{\{#if\s+user_name\}\}(.*?)\{\{\/if\}\}/s', (isset($q['user_name']) && $q['user_name'] !== '') ? '$1' : '', $s);
echo $s;
PHP;
        file_put_contents($dir . '/subject.tpl', $subjectContent);
        file_put_contents($dir . '/body.tpl', $bodyContent);
        file_put_contents($dir . '/subject.php', $substSubject);
        file_put_contents($dir . '/text.php', $substBody);
    }

    public function up(): void
    {
        if (!Schema::hasTable('letter_templates')) {
            return;
        }

        if (!Schema::hasColumn('letter_templates', 'slug')) {
            Schema::table('letter_templates', function (Blueprint $table) {
                $table->string('slug', 80)->nullable()->after('name');
            });
        }

        DB::table('letter_templates')->truncate();

        $now = now()->format('Y-m-d H:i:s');
        $defaults = [
            'sender' => self::SENDER_EMAIL,
            'sender_name' => self::SENDER_NAME,
            'receiver' => '',
            'template' => '',
            'created_at' => $now,
            'updated_at' => $now,
        ];

        // name = тема письма (русское название), slug = ключ для кода и папки
        $templates = [
            [
                'name' => 'Подтвердите регистрацию в «Совушкина школа»',
                'slug' => 'registration_confirm',
                'h1' => 'Подтверждение регистрации',
                'description' => 'Письмо после регистрации: ссылка и код подтверждения email.',
            ],
            [
                'name' => 'Оплата получена. Доступ активирован',
                'slug' => 'payment_success',
                'h1' => 'Оплата получена',
                'description' => 'После успешной оплаты подписки: доступ активирован/продлён.',
            ],
            [
                'name' => 'Подписка продлена',
                'slug' => 'charge_success_renewed',
                'h1' => 'Подписка продлена',
                'description' => 'После успешного рекуррентного списания.',
            ],
            [
                'name' => 'Не удалось списать оплату. Остались попытки',
                'slug' => 'charge_failed_attempts_left',
                'h1' => 'Ошибка списания (остались попытки)',
                'description' => 'Рекуррентное списание не прошло, но попытки ещё есть.',
            ],
            [
                'name' => 'Доступ завершён',
                'slug' => 'access_ended_after_cancel',
                'h1' => 'Доступ завершён',
                'description' => 'Подписка отменена и оплаченный период закончился.',
            ],
            [
                'name' => 'Доступ приостановлен',
                'slug' => 'access_suspended_after_3_attempts',
                'h1' => 'Доступ приостановлен',
                'description' => 'Не удалось списать оплату после 3 попыток.',
            ],
        ];

        foreach ($templates as $t) {
            DB::table('letter_templates')->insert(array_merge($t, $defaults));
        }

        $basePath = base_path('public/demo/email_templates');
        $read = static function (string $path): string {
            $full = base_path('public/demo/email_templates/' . $path);
            return is_file($full) ? file_get_contents($full) : '';
        };

        // 01_registration_confirm — в HTML уже используются {{переменная}}
        $subj01 = 'Подтвердите регистрацию в «Совушкина школа»';
        $body01 = $read('01_registration_confirm.html');
        $this->writeTemplateFiles('registration_confirm', $subj01, $body01 ?: '<p>Подтвердите регистрацию: {{confirm_url}}, код: {{confirm_code}}</p>');

        // 02_payment_success
        $subj02 = 'Оплата получена. Доступ активирован';
        $body02 = $read('02_payment_success.html');
        if ($body02 !== '') {
            $body02 = str_replace(['{amount}', '{plan_name}', '{paid_at}', '{access_period}', '{next_charge_at}', '{payment_method}', '{payment_id}', '{cabinet_url}'],
                ['{{amount}}', '{{plan_name}}', '{{paid_at}}', '{{access_period}}', '{{next_charge_at}}', '{{payment_method}}', '{{payment_id}}', '{{cabinet_url}}'], $body02);
        }
        $this->writeTemplateFiles('payment_success', $subj02, $body02 ?: '<p>Оплата получена. {{amount}} {{plan_name}}</p>');

        // 03_charge_success_renewed
        $subj03 = 'Подписка продлена';
        $body03 = $read('03_charge_success_renewed.html');
        if ($body03 !== '') {
            $body03 = str_replace(['{amount}', '{plan_name}', '{charged_at}', '{access_until}', '{next_charge_at}', '{payment_id}', '{cabinet_url}'],
                ['{{amount}}', '{{plan_name}}', '{{charged_at}}', '{{access_until}}', '{{next_charge_at}}', '{{payment_id}}', '{{cabinet_url}}'], $body03);
        }
        $this->writeTemplateFiles('charge_success_renewed', $subj03, $body03 ?: '<p>Подписка продлена. {{amount}} {{plan_name}}</p>');

        // 04_charge_failed_attempts_left
        $subj04 = 'Не удалось списать оплату. Остались попытки';
        $body04 = $read('04_charge_failed_attempts_left.html');
        if ($body04 !== '') {
            $body04 = str_replace(['{plan_name}', '{amount}', '{fail_reason}', '{attempt_number}', '{attempts_left}', '{next_attempt_at}', '{update_payment_url}'],
                ['{{plan_name}}', '{{amount}}', '{{fail_reason}}', '{{attempt_number}}', '{{attempts_left}}', '{{next_attempt_at}}', '{{update_payment_url}}'], $body04);
        }
        $this->writeTemplateFiles('charge_failed_attempts_left', $subj04, $body04 ?: '<p>Не удалось списать. Осталось попыток: {{attempts_left}}</p>');

        // 05_access_ended_after_cancel
        $subj05 = 'Доступ завершён';
        $body05 = $read('05_access_ended_after_cancel.html');
        if ($body05 !== '') {
            $body05 = str_replace(['{plan_name}', '{access_until}', '{renew_url}'], ['{{plan_name}}', '{{access_until}}', '{{renew_url}}'], $body05);
        }
        $this->writeTemplateFiles('access_ended_after_cancel', $subj05, $body05 ?: '<p>Доступ завершён. {{plan_name}} до {{access_until}}</p>');

        // 06_access_suspended_after_3_attempts
        $subj06 = 'Доступ приостановлен';
        $body06 = $read('06_access_suspended_after_3_attempts.html');
        if ($body06 !== '') {
            $body06 = str_replace(['{plan_name}', '{amount}', '{fail_reason}', '{last_attempt_at}', '{attempts_total}', '{update_payment_url}'],
                ['{{plan_name}}', '{{amount}}', '{{fail_reason}}', '{{last_attempt_at}}', '{{attempts_total}}', '{{update_payment_url}}'], $body06);
        }
        $this->writeTemplateFiles('access_suspended_after_3_attempts', $subj06, $body06 ?: '<p>Доступ приостановлен после {{attempts_total}} попыток.</p>');
    }

    public function down(): void
    {
        if (!Schema::hasTable('letter_templates')) {
            return;
        }
        DB::table('letter_templates')->truncate();
        if (Schema::hasColumn('letter_templates', 'slug')) {
            Schema::table('letter_templates', function (Blueprint $table) {
                $table->dropColumn('slug');
            });
        }
    }
};
