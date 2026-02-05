<?php

namespace App\Http\Controllers;

use App\Models\SubscriptionLevel;
use App\Models\SubscriptionOrder;
use App\Models\SubscriptionPaymentLog;
use App\Models\SubscriptionTariff;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class SubscriptionPaymentController extends Controller
{
    public function show(Request $request)
    {
        $orderId = (int) $request->query('order');
        $order = SubscriptionOrder::query()
            ->where('id', $orderId)
            ->where('user_id', Auth::id())
            ->firstOrFail();

        $levelIds = $this->parseLevelIds($order->subscription_level_ids, $order->levels);
        $levels = SubscriptionLevel::query()
            ->whereIn('id', $levelIds)
            ->orderBy('sort_order')
            ->get();

        $tariff = SubscriptionTariff::find($order->tariff);

        return view('subscriptions.checkout', [
            'order' => $order,
            'levels' => $levels,
            'tariff' => $tariff,
        ]);
    }

    public function create(Request $request)
    {
        $data = $request->validate([
            'levels' => ['required', 'array', 'min:1'],
            'levels.*' => ['integer'],
            'tariff_id' => ['required', 'integer'],
        ]);

        $user = Auth::user();
        $tariff = SubscriptionTariff::findOrFail($data['tariff_id']);
        $levelIds = array_values(array_unique(array_map('intval', $data['levels'])));
        $levels = SubscriptionLevel::query()
            ->whereIn('id', $levelIds)
            ->where('is_active', true)
            ->get();

        if ($levels->count() !== count($levelIds)) {
            return redirect()->route('subscriptions.index')
                ->withErrors(['levels' => 'Некорректный набор подписок.']);
        }

        $pricePerSub = (float) $tariff->price;
        $subtotal = $pricePerSub * count($levelIds);
        $discountPercent = $this->calculateDiscountPercent($user->id, $levelIds);
        $discount = round($subtotal * ($discountPercent / 100), 2);
        $total = max(0, $subtotal - $discount);

        $order = SubscriptionOrder::create([
            'user_id' => $user->id,
            'subscription_level_ids' => implode(',', $levelIds),
            'levels' => implode(',', $levelIds),
            'paid' => false,
            'date_subscription' => now()->toDateString(),
            'sum_subscription' => $total,
            'sum_without_discount' => $subtotal,
            'days' => (int) $tariff->days,
            'tariff' => $tariff->id,
            'errors' => 0,
            'auto' => true,
        ]);

        return redirect()->route('subscriptions.checkout', ['order' => $order->id]);
    }

    public function confirm(Request $request)
    {
        $data = $request->validate([
            'order_id' => ['required', 'integer'],
            'card_number' => ['required', 'regex:/^[0-9\s]{12,23}$/'],
            'card_exp' => ['required', 'regex:/^\d{2}\s*\/\s*\d{2}$/'],
            'card_cvc' => ['required', 'digits_between:3,4'],
            'card_holder' => ['required', 'string', 'min:2', 'max:255'],
            'payer_email' => ['required', 'email', 'max:255'],
        ]);

        $order = SubscriptionOrder::query()
            ->where('id', $data['order_id'])
            ->where('user_id', Auth::id())
            ->firstOrFail();

        if ($order->paid) {
            return redirect()->route('subscriptions.checkout', ['order' => $order->id]);
        }

        $levels = $this->parseLevelIds($order->subscription_level_ids, $order->levels);
        $count = count($levels);
        $perLevelPaidAmount = $count > 0 ? round(((float) $order->sum_subscription) / $count, 2) : 0;
        $pricePerSub = $count > 0 ? round(((float) $order->sum_without_discount) / $count, 2) : 0;
        $cardNumber = preg_replace('/\D+/', '', $data['card_number']);
        [$expMonthRaw, $expYearRaw] = array_map('trim', explode('/', $data['card_exp']));
        $expMonth = (int) $expMonthRaw;
        $expYear = (int) $expYearRaw;
        $expYear = 2000 + $expYear;
        $hash = $order->hash ?: hash('sha256', $cardNumber . '|' . $expMonth . '|' . $expYear);

        try {
            DB::transaction(function () use (
                $order,
                $hash,
                $cardNumber,
                $expMonth,
                $expYear,
                $data,
                $levels,
                $pricePerSub
            ) {
                $order->update([
                    'paid' => true,
                    'date_paid' => now(),
                    'hash' => $hash,
                    'date_till' => now()->addDays((int) $order->days)->toDateString(),
                ]);

                SubscriptionPaymentLog::create([
                    'subscription_order_id' => $order->id,
                    'status' => 'success',
                    'amount' => $order->sum_subscription,
                    'message' => 'Тестовая оплата (эмуляция YooKassa)',
                    'response_data' => [
                        'mode' => 'test',
                        'card' => [
                            'last4' => substr($cardNumber, -4),
                            'exp_month' => $expMonth,
                            'exp_year' => $expYear,
                            'holder' => $data['card_holder'],
                        ],
                        'payer_email' => $data['payer_email'],
                    ],
                    'payment_provider' => 'yookassa',
                    'transaction_id' => 'test_' . Str::uuid(),
                    'attempted_at' => now(),
                ]);

                $discountPercentForNext = $this->calculateDiscountPercent($order->user_id, $levels);
                foreach ($levels as $levelId) {
                    $this->createNextOrderForLevel(
                        $order->user_id,
                        (int) $levelId,
                        (int) $order->days,
                        $order->tariff,
                        $hash,
                        $pricePerSub,
                        $discountPercentForNext
                    );
                }
            });
        } catch (\Throwable $e) {
            report($e);

            return redirect()
                ->route('subscriptions.checkout', ['order' => $order->id])
                ->withErrors(['payment' => 'Не удалось обработать оплату. Проверьте данные и попробуйте снова.']);
        }

        return redirect()->route('subscriptions.index')
            ->with('success', 'Оплата прошла успешно. Подписки активированы.');
    }

    private function calculateDiscountPercent(int $userId, array $newLevels, ?int $excludeLevelId = null): int
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

        if ($excludeLevelId) {
            $activeLevels = array_values(array_filter($activeLevels, fn ($id) => $id !== $excludeLevelId));
        }

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

    private function createNextOrderForLevel(
        int $userId,
        int $levelId,
        int $days,
        ?int $tariffId,
        string $hash,
        float $pricePerSub,
        int $discountPercent = 0
    ): void {
        $today = Carbon::today();

        $active = SubscriptionOrder::query()
            ->where('user_id', $userId)
            ->where('paid', true)
            ->whereDate('date_till', '>=', $today->toDateString())
            ->where(function ($query) use ($levelId) {
                $query->where('levels', (string) $levelId)
                    ->orWhereRaw('FIND_IN_SET(?, subscription_level_ids)', [$levelId]);
            })
            ->orderByDesc('date_till')
            ->first();

        // Оплата наперёд: следующий платёж в день окончания текущего периода (date_next_pay = date_till активной подписки)
        $startDate = $active ? Carbon::parse($active->date_till) : $today;
        $periodEnd = $startDate->copy()->addDays($days); // конец периода, который покроет следующий платёж

        $existing = SubscriptionOrder::query()
            ->where('user_id', $userId)
            ->where('paid', false)
            ->where('levels', (string) $levelId)
            ->whereDate('date_next_pay', $startDate->toDateString())
            ->exists();

        if ($existing) {
            return;
        }

        $discount = round($pricePerSub * ($discountPercent / 100), 2);
        $nextAmount = max(0, $pricePerSub - $discount);

        SubscriptionOrder::create([
            'user_id' => $userId,
            'subscription_level_ids' => (string) $levelId,
            'levels' => (string) $levelId,
            'paid' => false,
            'date_subscription' => $today->toDateString(),
            'sum_subscription' => $nextAmount,
            'sum_without_discount' => $pricePerSub,
            'days' => $days,
            'date_next_pay' => $startDate->toDateString(),
            'sum_next_pay' => $nextAmount,
            'date_till' => $periodEnd->toDateString(),
            'tariff' => $tariffId,
            'hash' => $hash,
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
