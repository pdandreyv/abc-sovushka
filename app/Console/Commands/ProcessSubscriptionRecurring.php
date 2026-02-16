<?php

namespace App\Console\Commands;

use App\Models\SubscriptionLevel;
use App\Models\SubscriptionOrder;
use App\Models\SubscriptionPaymentLog;
use App\Models\SubscriptionTariff;
use App\Services\YooKassaService;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

class ProcessSubscriptionRecurring extends Command
{
    protected $signature = 'subscriptions:recurring';
    protected $description = 'Process recurring subscription payments (YooKassa or test mode)';

    public function __construct(
        private YooKassaService $yookassa
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $today = Carbon::today();
        $yesterday = $today->copy()->subDay();
        $tomorrow = $today->copy()->addDay();

        // Три попытки: 1) за день до окончания (date_next_pay = завтра), 2) в день окончания (date_next_pay = сегодня), 3) на следующий день (date_next_pay <= вчера). При errors >= 3 больше не пробуем.
        $order = SubscriptionOrder::query()
            ->where('paid', false)
            ->whereNotNull('date_next_pay')
            ->where('auto', true)
            ->where('errors', '<', 3)
            ->where(function ($q) use ($today, $tomorrow, $yesterday) {
                $q->where(function ($inner) use ($tomorrow) {
                    $inner->whereDate('date_next_pay', $tomorrow->toDateString())
                        ->where('errors', 0);
                })->orWhere(function ($inner) use ($today) {
                    $inner->whereDate('date_next_pay', $today->toDateString())
                        ->where('errors', '<', 2);
                })->orWhere(function ($inner) use ($yesterday) {
                    $inner->whereDate('date_next_pay', '<=', $yesterday->toDateString())
                        ->where('errors', '<', 3);
                });
            })
            ->orderBy('date_next_pay')
            ->first();

        if (!$order) {
            $this->info('No orders for recurring processing.');
            return self::SUCCESS;
        }

        $levelId = $this->resolveLevelId($order);
        if (!$levelId) {
            $this->error('Order #' . $order->id . ' has invalid level set.');
            return self::FAILURE;
        }
        $basePrice = $this->resolveBasePrice($order, $levelId);
        $discountPercent = $this->calculateDiscountPercent($order->user_id, [$levelId]);
        $discount = round($basePrice * ($discountPercent / 100), 2);
        $amount = max(0, $basePrice - $discount);

        $dateNextPay = Carbon::parse($order->date_next_pay);
        $attempt = $this->getAttemptNumber($dateNextPay, $today, $order->errors);

        $success = false;
        $yookassaPaymentId = null;

        if ($this->yookassa->isConfigured() && $order->hash) {
            $user = $order->user;
            $receiptParams = [
                'customer_email' => $user?->email,
                'customer_phone' => $user?->phone,
                'customer_name' => $user ? trim(implode(' ', array_filter([
                    $user->last_name ?? '',
                    $user->first_name ?? '',
                    $user->middle_name ?? '',
                ]))) : '',
            ];
            $result = $this->yookassa->createRecurringPayment(
                $order->hash,
                $amount,
                'Продление подписки №' . $order->id,
                $receiptParams
            );

            if (isset($result['error'])) {
                $order->increment('errors');
                SubscriptionPaymentLog::create([
                    'subscription_order_id' => $order->id,
                    'status' => 'error',
                    'amount' => $amount,
                    'message' => 'ЮKassa: ' . $result['error'],
                    'response_data' => $result,
                    'payment_provider' => 'yookassa',
                    'transaction_id' => 'recurring_' . Str::uuid(),
                    'attempted_at' => now(),
                ]);
                $this->error('Recurring payment failed for order #' . $order->id . ': ' . $result['error']);
                return self::SUCCESS;
            }

            $yookassaPaymentId = $result['id'] ?? null;
            $success = ($result['status'] ?? '') === 'succeeded' || ! empty($result['paid'] ?? false);

            if (! $success && $yookassaPaymentId) {
                $payment = $this->yookassa->getPayment($yookassaPaymentId);
                $success = ($payment['status'] ?? '') === 'succeeded' && ! empty($payment['paid'] ?? false);
            }
        } else {
            $success = true;
        }

        if ($success) {
            // Не меняем date_till обрабатываемого заказа — он уже создан с правильной датой (конец оплаченного периода). Только отмечаем оплату.
            $order->update([
                'paid' => true,
                'date_paid' => now(),
                'sum_subscription' => $amount,
                'sum_without_discount' => $basePrice,
                'hash' => $order->hash ?: Str::random(40),
            ]);

            SubscriptionPaymentLog::create([
                'subscription_order_id' => $order->id,
                'status' => 'success',
                'amount' => $amount,
                'message' => $this->yookassa->isConfigured() ? 'Рекуррентное списание через ЮKassa' : 'Тестовое рекуррентное списание',
                'response_data' => $yookassaPaymentId ? ['yookassa_payment_id' => $yookassaPaymentId] : ['mode' => 'test'],
                'payment_provider' => 'yookassa',
                'transaction_id' => $yookassaPaymentId ?? 'test_' . Str::uuid(),
                'attempted_at' => now(),
            ]);

            // Дата следующего списания для нового заказа = конец периода оплаченного заказа (order->date_till, мы его не меняем).
            $nextOrderStart = Carbon::parse($order->date_till);
            $this->createNextOrder($order, $basePrice, $levelId, $discountPercent, $nextOrderStart->toDateString());

            $this->info('Recurring payment successful for order #' . $order->id);
        } else {
            $order->increment('errors');

            SubscriptionPaymentLog::create([
                'subscription_order_id' => $order->id,
                'status' => 'error',
                'amount' => $amount,
                'message' => $this->yookassa->isConfigured() ? 'ЮKassa: платёж не прошёл' : 'Ошибка тестового списания',
                'response_data' => $yookassaPaymentId ? ['yookassa_payment_id' => $yookassaPaymentId] : ['mode' => 'test'],
                'payment_provider' => 'yookassa',
                'transaction_id' => $yookassaPaymentId ?? 'test_' . Str::uuid(),
                'attempted_at' => now(),
            ]);

            $this->error('Recurring payment failed for order #' . $order->id . ' (errors=' . ($order->errors + 1) . ')');
        }

        return self::SUCCESS;
    }

    private function getAttemptNumber(Carbon $dateNextPay, Carbon $today, int $errors): int
    {
        $tomorrow = $today->copy()->addDay();
        $yesterday = $today->copy()->subDay();
        $d = $dateNextPay->toDateString();
        if ($d === $tomorrow->toDateString()) {
            return 1;
        }
        if ($d === $today->toDateString()) {
            return 2;
        }
        if ($dateNextPay->lte($yesterday)) {
            return 3;
        }
        return 2;
    }

    private function resolveBasePrice(SubscriptionOrder $order, int $levelId): float
    {
        if ($order->sum_without_discount) {
            return (float) $order->sum_without_discount;
        }

        if ($order->tariff) {
            $tariff = SubscriptionTariff::find($order->tariff);
            if ($tariff) {
                return (float) $tariff->price;
            }
        }

        $level = SubscriptionLevel::find($levelId);
        return $level ? (float) $level->price : 0;
    }

    private function resolveLevelId(SubscriptionOrder $order): ?int
    {
        if (!empty($order->levels) && !str_contains((string) $order->levels, ',')) {
            return (int) $order->levels;
        }
        $ids = $this->parseLevelIds($order->subscription_level_ids, $order->levels);
        if (count($ids) === 1) {
            return (int) $ids[0];
        }
        return null;
    }

    /**
     * Скидка для продления: считаем оплаченные подписки с уникальным level_id и актуальной датой (date_till >= сегодня),
     * объединяем с уровнем продления и даём 10% при 2, 15% при 3+, 20% при всех подписках.
     */
    private function calculateDiscountPercent(int $userId, array $newLevels): int
    {
        $activeLevels = SubscriptionOrder::query()
            ->where('user_id', $userId)
            ->where('paid', true)
            ->whereDate('date_till', '>=', now()->toDateString())
            ->get(['levels', 'subscription_level_ids'])
            ->flatMap(function ($order) {
                return $this->parseLevelIds($order->subscription_level_ids, $order->levels);
            })
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values()
            ->toArray();

        $merged = array_unique(array_merge($activeLevels, $newLevels));
        $count = count($merged);

        $allCount = SubscriptionLevel::query()->where('is_active', true)->count();
        if ($allCount > 0 && $count >= $allCount) {
            return 20;
        }
        if ($count >= 3) {
            return 15;
        }
        if ($count === 2) {
            return 10;
        }
        return 0;
    }

    private function createNextOrder(SubscriptionOrder $order, float $basePrice, int $levelId, int $discountPercent, ?string $periodEndDate = null): void
    {
        $days = (int) $order->days;
        $currentTill = $periodEndDate ? Carbon::parse($periodEndDate) : Carbon::parse($order->date_till);
        $nextTill = $currentTill->copy()->addDays($days);

        // Используем тот же процент скидки, что и для текущей оплаты (набор активных уровней после оплаты тот же)
        $nextDiscount = round($basePrice * ($discountPercent / 100), 2);
        $nextAmount = max(0, $basePrice - $nextDiscount);

        SubscriptionOrder::create([
            'user_id' => $order->user_id,
            'subscription_level_ids' => (string) $order->levels,
            'levels' => (string) $order->levels,
            'paid' => false,
            'date_subscription' => Carbon::today()->toDateString(),
            'sum_subscription' => $nextAmount,
            'sum_without_discount' => $basePrice,
            'days' => $days,
            'date_next_pay' => $currentTill->toDateString(),
            'sum_next_pay' => $nextAmount,
            'date_till' => $nextTill->toDateString(),
            'tariff' => $order->tariff,
            'hash' => $order->hash,
            'card_last4' => $order->card_last4,
            'errors' => 0,
            'auto' => true,
        ]);
    }

    private function parseLevelIds($subscriptionLevelIds, ?string $levels = null): array
    {
        if (is_array($subscriptionLevelIds)) {
            return array_values(array_unique(array_map('intval', $subscriptionLevelIds)));
        }

        $source = $subscriptionLevelIds ?: $levels;
        if (!is_string($source) || trim($source) === '') {
            return [];
        }

        return array_values(array_unique(array_map(
            'intval',
            array_filter(array_map('trim', explode(',', $source)))
        )));
    }
}
