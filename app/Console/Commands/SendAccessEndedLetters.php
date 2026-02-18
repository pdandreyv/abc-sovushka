<?php

namespace App\Console\Commands;

use App\Models\SubscriptionOrder;
use App\Models\SubscriptionLevel;
use App\Services\LetterTemplateService;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

class SendAccessEndedLetters extends Command
{
    protected $signature = 'subscriptions:send-access-ended-letters';
    protected $description = 'Отправить письма «Доступ завершён» по заказам с отменённым автопродлением и истёкшим периодом';

    public function handle(LetterTemplateService $letterTemplates): int
    {
        $today = Carbon::today()->toDateString();

        $orders = SubscriptionOrder::query()
            ->where('paid', true)
            ->where('auto', false)
            ->whereDate('date_till', '<', $today)
            ->whereNull('access_ended_notified_at')
            ->with('user')
            ->get();

        foreach ($orders as $order) {
            $user = $order->user;
            if (! $user || ! $user->email) {
                $order->update(['access_ended_notified_at' => now()]);
                continue;
            }
            $levelIds = $this->parseLevelIds($order->subscription_level_ids, $order->levels);
            $planName = SubscriptionLevel::query()
                ->whereIn('id', $levelIds)
                ->pluck('title')
                ->implode(', ') ?: 'Подписка';
            $letterTemplates->send('access_ended_after_cancel', $user->email, [
                'plan_name' => $planName,
                'access_until' => $order->date_till ? $order->date_till->format('d.m.Y') : '',
                'renew_url' => route('subscriptions.index'),
            ]);
            $order->update(['access_ended_notified_at' => now()]);
            $this->info('Sent access_ended_after_cancel for order #' . $order->id . ' to ' . $user->email);
        }

        return self::SUCCESS;
    }

    private function parseLevelIds($subscriptionLevelIds, ?string $levels = null): array
    {
        if (is_array($subscriptionLevelIds)) {
            return array_values(array_unique(array_map('intval', $subscriptionLevelIds)));
        }
        $source = $subscriptionLevelIds ?: $levels;
        if (! is_string($source) || trim($source) === '') {
            return [];
        }
        return array_values(array_unique(array_map(
            'intval',
            array_filter(array_map('trim', explode(',', $source)))
        )));
    }
}
