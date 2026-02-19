<?php

namespace App\Http\Controllers;

use App\Models\Promotion;
use App\Models\SubscriptionLevel;
use App\Models\SubscriptionOrder;
use App\Models\SubscriptionTariff;
use App\Services\YooKassaService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class PromotionController extends Controller
{
    public function __construct(
        private YooKassaService $yookassa
    ) {}
    /**
     * Страница акции: показываем только при наличии неиспользованной акции у пользователя.
     */
    public function index(): View|RedirectResponse
    {
        $promotion = Promotion::query()
            ->where('user_id', Auth::id())
            ->where('used', false)
            ->first();

        if (!$promotion) {
            return redirect()->route('subscriptions.index');
        }

        $levelIds = $promotion->getLevelIds();
        $levels = SubscriptionLevel::query()
            ->whereIn('id', $levelIds)
            ->orderBy('sort_order')
            ->get();

        if ($levels->isEmpty()) {
            return redirect()->route('subscriptions.index');
        }

        $tariff = SubscriptionTariff::find($promotion->tariff_id);
        $today = Carbon::today();
        $dateTill = $today->copy()->addDays($promotion->free_days);
        $nextChargeDate = $today->copy()->addDays(max(0, $promotion->free_days - 1));

        return view('promotions.index', [
            'promotion' => $promotion,
            'levels' => $levels,
            'tariff' => $tariff,
            'date_till' => $dateTill,
            'next_charge_date' => $nextChargeDate,
        ]);
    }

    /**
     * Создать заказ по акции (привязка карты) и сразу перенаправить на страницу привязки карты ЮKassa.
     */
    public function createOrder(): RedirectResponse
    {
        $promotion = Promotion::query()
            ->where('user_id', Auth::id())
            ->where('used', false)
            ->firstOrFail();

        $levelIds = $promotion->getLevelIds();
        if (empty($levelIds)) {
            return redirect()->route('promotion.index')
                ->withErrors(['promotion' => 'В акции не указаны уровни подписок.']);
        }

        $tariff = SubscriptionTariff::find($promotion->tariff_id);
        if (!$tariff) {
            return redirect()->route('promotion.index')
                ->withErrors(['promotion' => 'Тариф акции не найден.']);
        }

        $order = SubscriptionOrder::create([
            'user_id' => Auth::id(),
            'promotion_id' => $promotion->id,
            'subscription_level_ids' => $promotion->subscription_level_ids,
            'levels' => implode(',', $levelIds),
            'paid' => false,
            'date_subscription' => now()->toDateString(),
            'sum_subscription' => 0,
            'sum_without_discount' => price_rub_ceil($promotion->special_price),
            'days' => $promotion->free_days,
            'tariff' => $promotion->tariff_id,
            'errors' => 0,
            'auto' => true,
            'discount_code' => null,
        ]);

        if (!$this->yookassa->isConfigured()) {
            return redirect()->route('subscriptions.checkout', ['order' => $order->id])
                ->withErrors(['payment' => 'Оплата через ЮKassa не настроена.']);
        }

        $returnUrl = route('subscriptions.yookassa.return', ['order_id' => $order->id]);
        $result = $this->yookassa->createPaymentMethod($returnUrl);

        if (isset($result['error'])) {
            return redirect()->route('promotion.index')
                ->withErrors(['payment' => 'ЮKassa: ' . $result['error']]);
        }

        $order->update(['yookassa_payment_id' => $result['id']]);

        if (!empty($result['confirmation_url'])) {
            return redirect()->away($result['confirmation_url']);
        }

        return redirect()->route('promotion.index')
            ->withErrors(['payment' => 'Не получена ссылка на привязку карты.']);
    }
}
