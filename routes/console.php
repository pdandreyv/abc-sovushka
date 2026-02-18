<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Удаление логов действий пользователей старше 6 месяцев (ежедневно)
Schedule::command('user-activity-logs:clean')->daily();

// Письма «Доступ завершён» по заказам с отменённым автопродлением и истёкшим периодом (ежедневно)
Schedule::command('subscriptions:send-access-ended-letters')->daily();
