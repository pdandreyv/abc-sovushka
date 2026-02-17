<?php

namespace App\Console\Commands;

use App\Models\UserActivityLog;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

class CleanUserActivityLogs extends Command
{
    protected $signature = 'user-activity-logs:clean';
    protected $description = 'Удалить логи действий пользователей старше 6 месяцев';

    public function handle(): int
    {
        $before = Carbon::now()->subMonths(6);
        $deleted = UserActivityLog::query()->where('created_at', '<', $before)->delete();
        $this->info("Удалено записей логов: {$deleted} (старше {$before->format('Y-m-d')}).");
        return self::SUCCESS;
    }
}
