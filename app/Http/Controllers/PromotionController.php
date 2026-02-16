<?php

namespace App\Http\Controllers;

use App\Models\Promotion;
use App\Models\SubscriptionLevel;
use App\Models\SubscriptionOrder;
use App\Models\SubscriptionTariff;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class PromotionController extends Controller
{
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
     * Создать заказ по акции (привязка карты) и перенаправить на checkout.
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
            'sum_without_discount' => (float) $promotion->special_price,
            'days' => $promotion->free_days,
            'tariff' => $promotion->tariff_id,
            'errors' => 0,
            'auto' => true,
            'discount_code' => null,
        ]);

        return redirect()->route('subscriptions.checkout', ['order' => $order->id]);
    }
}
