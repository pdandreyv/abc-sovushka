<?php

namespace App\Http\Controllers;

use App\Models\DiscountCode;
use App\Models\SubscriptionLevel;
use App\Models\SubscriptionOrder;
use App\Models\SubscriptionPaymentLog;
use App\Models\Promotion;
use App\Models\SubscriptionTariff;
use App\Services\LetterTemplateService;
use App\Services\YooKassaService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class SubscriptionPaymentController extends Controller
{
    public function __construct(
        private YooKassaService $yookassa,
        private LetterTemplateService $letterTemplates
    ) {}

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
        $useYookassa = $this->yookassa->isConfigured();
        $yookassaRecurringEnabled = $this->yookassa->isRecurringEnabled();
        $isPromotionAttach = $order->promotion_id && (float) $order->sum_subscription <= 0;

        return view('subscriptions.checkout', [
            'order' => $order,
            'levels' => $levels,
            'tariff' => $tariff,
            'useYookassa' => $useYookassa,
            'yookassaRecurringEnabled' => $yookassaRecurringEnabled,
            'isPromotionAttach' => $isPromotionAttach,
        ]);
    }

    /**
     * Создать платёж в ЮKassa и перенаправить пользователя на страницу оплаты.
     */
    public function redirectToPayment(Request $request)
    {
        $request->validate(['order_id' => ['required', 'integer']]);

        $order = SubscriptionOrder::query()
            ->where('id', $request->order_id)
            ->where('user_id', Auth::id())
            ->firstOrFail();

        if ($order->paid) {
            return redirect()->route('subscriptions.index')->with('success', 'Заказ уже оплачен.');
        }

        if (! $this->yookassa->isConfigured()) {
            return redirect()->route('subscriptions.checkout', ['order' => $order->id])
                ->withErrors(['payment' => 'Оплата через ЮKassa не настроена.']);
        }

        $returnUrl = route('subscriptions.yookassa.return', ['order_id' => $order->id]);

        // Привязка карты по акции: используем привязку на нулевую сумму (POST /payment_methods без amount)
        if ($order->promotion_id && (float) $order->sum_subscription <= 0) {
            $result = $this->yookassa->createPaymentMethod($returnUrl);
            if (isset($result['error'])) {
                return redirect()->route('subscriptions.checkout', ['order' => $order->id])
                    ->withErrors(['payment' => 'ЮKassa: ' . $result['error']]);
            }
            $order->update(['yookassa_payment_id' => $result['id']]);
            if (! empty($result['confirmation_url'])) {
                return redirect()->away($result['confirmation_url']);
            }
            return redirect()->route('subscriptions.checkout', ['order' => $order->id])
                ->withErrors(['payment' => 'Не получена ссылка на привязку карты.']);
        }

        $user = $order->user;
        $customerName = $user ? trim(implode(' ', array_filter([
            $user->last_name ?? '',
            $user->first_name ?? '',
            $user->middle_name ?? '',
        ]))) : '';
        $result = $this->yookassa->createPayment([
            'amount' => (float) $order->sum_subscription,
            'return_url' => $returnUrl,
            'description' => 'Подписка №' . $order->id,
            'order_id' => $order->id,
            'customer_email' => $user?->email,
            'customer_phone' => $user?->phone,
            'customer_name' => $customerName,
        ]);

        if (isset($result['error'])) {
            return redirect()->route('subscriptions.checkout', ['order' => $order->id])
                ->withErrors(['payment' => 'ЮKassa: ' . $result['error']]);
        }

        $order->update(['yookassa_payment_id' => $result['id']]);

        if (! empty($result['confirmation_url'])) {
            return redirect()->away($result['confirmation_url']);
        }

        return redirect()->route('subscriptions.checkout', ['order' => $order->id])
            ->withErrors(['payment' => 'Не получена ссылка на оплату.']);
    }

    /**
     * Возврат пользователя после оплаты на ЮKassa (return_url).
     */
    public function returnFromYooKassa(Request $request)
    {
        $orderId = (int) $request->query('order_id');
        $order = SubscriptionOrder::query()
            ->where('id', $orderId)
            ->where('user_id', Auth::id())
            ->first();

        if (! $order) {
            return redirect()->route('subscriptions.index')->withErrors(['payment' => 'Заказ не найден.']);
        }

        if ($order->paid) {
            return redirect()->route('subscriptions.index')->with('success', 'Оплата прошла успешно. Подписки активированы.');
        }

        if (! $order->yookassa_payment_id || ! $this->yookassa->isConfigured()) {
            return redirect()->route('subscriptions.checkout', ['order' => $order->id])
                ->withErrors(['payment' => 'Невозможно проверить статус платежа.']);
        }

        $payment = $this->yookassa->getPayment($order->yookassa_payment_id);

        // Если это был платёж — обрабатываем успех
        if (! isset($payment['error']) && ($payment['status'] ?? '') === 'succeeded' && ! empty($payment['paid'])) {
            $paymentMethodId = isset($payment['payment_method']['id']) ? $payment['payment_method']['id'] : null;
            $cardLast4 = isset($payment['payment_method']['card']['last4']) ? $this->normalizeCardLast4($payment['payment_method']['card']['last4']) : null;
            $this->processPaymentSuccess($order, $paymentMethodId, $cardLast4);
            $this->markPromotionUsedIfActivated($order->id);
            $message = $order->promotion_id
                ? 'Карта привязана. Бесплатный период активирован.'
                : 'Оплата прошла успешно. Подписки активированы.';
            return redirect()->route('subscriptions.index')->with('success', $message);
        }

        // Привязка на нулевую сумму: в yookassa_payment_id хранится id способа оплаты
        if ($order->promotion_id && (float) $order->sum_subscription <= 0) {
            $pm = $this->yookassa->getPaymentMethod($order->yookassa_payment_id);
            if (! isset($pm['error']) && ($pm['status'] ?? '') === 'active' && ! empty($pm['saved'])) {
                $hash = $pm['id'];
                $cardLast4 = isset($pm['card']['last4']) ? $this->normalizeCardLast4($pm['card']['last4']) : null;
                $levels = $this->parseLevelIds($order->subscription_level_ids, $order->levels);
                $this->processPromotionPaymentSuccess($order, $hash, $levels, $cardLast4);
                $this->markPromotionUsedIfActivated($order->id);
                return redirect()->route('subscriptions.index')
                    ->with('success', 'Карта привязана. Бесплатный период активирован.');
            }
            if (isset($pm['error'])) {
                return redirect()->route('subscriptions.checkout', ['order' => $order->id])
                    ->withErrors(['payment' => 'ЮKassa: ' . $pm['error']]);
            }
        } elseif (isset($payment['error'])) {
            return redirect()->route('subscriptions.checkout', ['order' => $order->id])
                ->withErrors(['payment' => 'ЮKassa: ' . $payment['error']]);
        }

        return redirect()->route('subscriptions.checkout', ['order' => $order->id])
            ->with('info', 'Ожидаем подтверждение оплаты. Если платёж прошёл, страница обновится автоматически.');
    }

    /**
     * Webhook от ЮKassa (настроить URL в ЛК: Интеграция → HTTP-уведомления).
     * События: payment.succeeded, payment.canceled.
     */
    public function webhook(Request $request)
    {
        $body = $request->all();
        $event = $body['event'] ?? '';
        $object = $body['object'] ?? [];

        if ($event !== 'payment.succeeded') {
            return response()->json(['ok' => true], 200);
        }

        $orderId = (int) ($object['metadata']['order_id'] ?? 0);
        if (! $orderId) {
            return response()->json(['ok' => false, 'message' => 'No order_id in metadata'], 200);
        }

        $order = SubscriptionOrder::query()->find($orderId);
        if (! $order || $order->paid) {
            return response()->json(['ok' => true], 200);
        }

        $paymentMethodId = isset($object['payment_method']['id']) ? $object['payment_method']['id'] : null;
        $cardLast4 = isset($object['payment_method']['card']['last4']) ? $this->normalizeCardLast4($object['payment_method']['card']['last4']) : null;
        $this->processPaymentSuccess($order, $paymentMethodId, $cardLast4);
        $this->markPromotionUsedIfActivated($order->id);

        return response()->json(['ok' => true], 200);
    }

    /**
     * Если заказ по акции успешно оплачен/активирован — помечаем акцию как использованную.
     */
    private function markPromotionUsedIfActivated(int $orderId): void
    {
        $order = SubscriptionOrder::find($orderId);
        if (! $order || ! $order->promotion_id || ! $order->paid) {
            return;
        }
        Promotion::where('id', $order->promotion_id)->update([
            'used' => true,
            'used_at' => now(),
        ]);
    }

    /**
     * Отметить заказ оплаченным, сохранить способ оплаты для автоплатежей, создать следующие заказы.
     */
    private function processPaymentSuccess(SubscriptionOrder $order, ?string $paymentMethodId, ?string $cardLast4 = null): void
    {
        if ($order->paid) {
            return;
        }

        $levels = $this->parseLevelIds($order->subscription_level_ids, $order->levels);
        $count = count($levels);
        $hash = $paymentMethodId ?? $order->hash ?? Str::random(40);

        if ($order->promotion_id) {
            $this->processPromotionPaymentSuccess($order, $hash, $levels, $cardLast4);
            return;
        }

        $pricePerSub = $count > 0 ? price_rub_ceil(((float) $order->sum_without_discount) / $count) : 0;
        $updateData = [
            'paid' => true,
            'date_paid' => now(),
            'hash' => $hash,
            'date_till' => now()->addDays((int) $order->days)->toDateString(),
        ];
        if ($cardLast4 !== null) {
            $updateData['card_last4'] = $cardLast4;
        }

        DB::transaction(function () use ($order, $updateData, $hash, $levels, $pricePerSub, $cardLast4) {
            $order->update($updateData);

            SubscriptionPaymentLog::create([
                'subscription_order_id' => $order->id,
                'status' => 'success',
                'amount' => $order->sum_subscription,
                'message' => 'Оплата через ЮKassa',
                'response_data' => ['provider' => 'yookassa'],
                'payment_provider' => 'yookassa',
                'transaction_id' => $order->yookassa_payment_id ?? 'webhook',
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
                    $discountPercentForNext,
                    $cardLast4
                );
            }
        });

        $this->sendPaymentSuccessLetter($order);
    }

    /**
     * Обработка успешной привязки карты по акции: активируем бесплатные дни, создаём следующие заказы по спец. цене, помечаем акцию использованной.
     */
    private function processPromotionPaymentSuccess(SubscriptionOrder $order, string $hash, array $levels, ?string $cardLast4): void
    {
        $promotion = Promotion::find($order->promotion_id);
        if (!$promotion || $promotion->used) {
            return;
        }

        $freeDays = (int) $promotion->free_days;
        $specialPrice = price_rub_ceil($promotion->special_price);
        $today = Carbon::today();
        $dateTill = $today->copy()->addDays($freeDays);
        $nextChargeDate = $today->copy()->addDays(max(0, $freeDays - 1));
        $tariff = $promotion->tariff_id;
        $tariffDays = $promotion->tariff ? (SubscriptionTariff::find($promotion->tariff_id)?->days ?? 30) : 30;
        $levelsStr = implode(',', $levels);
        $periodEnd = $nextChargeDate->copy()->addDays($tariffDays);

        DB::transaction(function () use ($order, $promotion, $hash, $levelsStr, $cardLast4, $dateTill, $nextChargeDate, $specialPrice, $tariff, $tariffDays, $periodEnd) {
            $order->update([
                'paid' => true,
                'date_paid' => now(),
                'hash' => $hash,
                'date_till' => $dateTill->toDateString(),
                'card_last4' => $cardLast4,
            ]);

            SubscriptionPaymentLog::create([
                'subscription_order_id' => $order->id,
                'status' => 'success',
                'amount' => $order->sum_subscription,
                'message' => 'Привязка карты по акции (ЮKassa)',
                'response_data' => ['provider' => 'yookassa', 'promotion_id' => $promotion->id],
                'payment_provider' => 'yookassa',
                'transaction_id' => $order->yookassa_payment_id ?? 'webhook',
                'attempted_at' => now(),
            ]);

            // Один рекуррентный заказ на все уровни с общей суммой по спец. цене (не разбиваем на отдельные заказы).
            SubscriptionOrder::create([
                'user_id' => $order->user_id,
                'subscription_level_ids' => $levelsStr,
                'levels' => $levelsStr,
                'paid' => false,
                'date_subscription' => $order->date_subscription,
                'sum_subscription' => $specialPrice,
                'sum_without_discount' => $specialPrice,
                'days' => $tariffDays,
                'date_next_pay' => $nextChargeDate->toDateString(),
                'sum_next_pay' => $specialPrice,
                'date_till' => $periodEnd->toDateString(),
                'tariff' => $tariff,
                'hash' => $hash,
                'card_last4' => $cardLast4,
                'errors' => 0,
                'auto' => true,
            ]);

            $promotion->update(['used' => true, 'used_at' => now()]);
        });

        $this->sendPaymentSuccessLetter($order);
    }

    public function create(Request $request)
    {
        $data = $request->validate([
            'levels' => ['required', 'array', 'min:1'],
            'levels.*' => ['integer'],
            'tariff_id' => ['required', 'integer'],
            'discount_code' => ['nullable', 'string', 'max:64'],
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

        $pricePerSub = price_rub_ceil($tariff->price);
        $subtotal = $pricePerSub * count($levelIds);
        $discountPercent = $this->calculateDiscountPercent($user->id, $levelIds);
        $discount = price_rub_ceil($subtotal * ($discountPercent / 100));
        $total = max(0, $subtotal - $discount);
        $appliedCode = null;

        if (! empty(trim($data['discount_code'] ?? ''))) {
            $promo = $this->resolvePromoCode(trim($data['discount_code']), $levelIds, $user->id);
            if ($promo) {
                $appliedCode = $promo->code;
                $discount = price_rub_ceil($subtotal * ($promo->discount_percent / 100));
                $total = max(0, $subtotal - $discount);
            }
        }

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
            'discount_code' => $appliedCode,
        ]);

        // Бесплатный заказ (100% скидка) — сразу активируем, без страницы оплаты
        $orderSum = (float) $order->sum_subscription;
        if ($orderSum <= 0) {
            $this->processPaymentSuccess($order, null);
            return redirect()->route('subscriptions.index')
                ->with('success', 'Подписка оформлена бесплатно по промокоду. Подписки активированы.');
        }

        return redirect()->route('subscriptions.checkout', ['order' => $order->id]);
    }

    /**
     * Проверка промокода при создании заказа. Возвращает модель или null.
     */
    private function resolvePromoCode(string $codeRaw, array $levelIds, int $userId): ?DiscountCode
    {
        $promo = DiscountCode::query()
            ->whereRaw('LOWER(code) = ?', [mb_strtolower($codeRaw)])
            ->where('display', true)
            ->first();

        if (! $promo || ! $promo->isValidOn(now()->toDateString()) || ! $promo->hasUsagesLeft()) {
            return null;
        }

        if (SubscriptionOrder::query()->where('user_id', $userId)->where('discount_code', $promo->code)->exists()) {
            return null;
        }

        $codeLevelIds = $promo->level_ids;
        if (empty($codeLevelIds) || empty(array_intersect($levelIds, $codeLevelIds))) {
            return null;
        }

        return $promo;
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
        $cardNumber = preg_replace('/\D+/', '', $data['card_number']);
        $cardLast4 = strlen($cardNumber) >= 4 ? substr($cardNumber, -4) : null;
        [$expMonthRaw, $expYearRaw] = array_map('trim', explode('/', $data['card_exp']));
        $expMonth = (int) $expMonthRaw;
        $expYear = (int) $expYearRaw;
        $expYear = 2000 + $expYear;
        $hash = $order->hash ?: hash('sha256', $cardNumber . '|' . $expMonth . '|' . $expYear);

        // Заказ по акции: один рекуррентный заказ на все уровни, без разбиения по уровням.
        if ($order->promotion_id) {
            $this->processPromotionPaymentSuccess($order, $hash, $levels, $cardLast4);
            $this->markPromotionUsedIfActivated($order->id);
            return redirect()->route('subscriptions.index')
                ->with('success', 'Карта привязана. Бесплатный период активирован.');
        }

        $count = count($levels);
        $perLevelPaidAmount = $count > 0 ? price_rub_ceil(((float) $order->sum_subscription) / $count) : 0;
        $pricePerSub = $count > 0 ? price_rub_ceil(((float) $order->sum_without_discount) / $count) : 0;

        try {
            DB::transaction(function () use (
                $order,
                $hash,
                $cardLast4,
                $cardNumber,
                $expMonth,
                $expYear,
                $data,
                $levels,
                $pricePerSub
            ) {
                $updateData = [
                    'paid' => true,
                    'date_paid' => now(),
                    'hash' => $hash,
                    'date_till' => now()->addDays((int) $order->days)->toDateString(),
                ];
                if ($cardLast4 !== null) {
                    $updateData['card_last4'] = $cardLast4;
                }
                $order->update($updateData);

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
                        $discountPercentForNext,
                        $cardLast4
                    );
                }
            });
        } catch (\Throwable $e) {
            report($e);

            return redirect()
                ->route('subscriptions.checkout', ['order' => $order->id])
                ->withErrors(['payment' => 'Не удалось обработать оплату. Проверьте данные и попробуйте снова.']);
        }

        $this->sendPaymentSuccessLetter($order->fresh());

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
        int $discountPercent = 0,
        ?string $cardLast4 = null
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

        $pricePerSub = price_rub_ceil($pricePerSub);
        $discount = price_rub_ceil($pricePerSub * ($discountPercent / 100));
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
            'card_last4' => $cardLast4,
            'errors' => 0,
            'auto' => true,
        ]);
    }

    private function sendPaymentSuccessLetter(SubscriptionOrder $order): void
    {
        $user = $order->user;
        if (! $user || ! $user->email) {
            return;
        }
        $levelIds = $this->parseLevelIds($order->subscription_level_ids, $order->levels);
        $planNames = SubscriptionLevel::query()
            ->whereIn('id', $levelIds)
            ->pluck('title')
            ->toArray();
        $planName = implode(', ', $planNames) ?: 'Подписка';
        $nextOrder = SubscriptionOrder::query()
            ->where('user_id', $order->user_id)
            ->where('paid', false)
            ->orderBy('date_next_pay')
            ->first();
        $this->letterTemplates->send('payment_success', $user->email, [
            'subject' => 'Оплата получена. Доступ активирован',
            'year' => now()->year,
            'amount' => number_format((float) $order->sum_subscription, 0, ',', ' '),
            'plan_name' => $planName,
            'paid_at' => $order->date_paid ? $order->date_paid->format('d.m.Y H:i') : now()->format('d.m.Y H:i'),
            'access_period' => $order->date_till ? $order->date_till->format('d.m.Y') : '',
            'next_charge_at' => $nextOrder && $nextOrder->date_next_pay ? $nextOrder->date_next_pay->format('d.m.Y') : '',
            'payment_method' => 'Банковская карта',
            'payment_id' => $order->yookassa_payment_id ?? (string) $order->id,
            'cabinet_url' => route('subscriptions.index'),
        ]);
    }

    private function normalizeCardLast4(?string $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }
        $digits = preg_replace('/\D/', '', $value);
        return strlen($digits) >= 4 ? substr($digits, -4) : null;
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
