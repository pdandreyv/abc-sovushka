<?php

namespace App\Http\Controllers;

use App\Models\SubscriptionLevel;
use App\Models\SubscriptionOrder;
use App\Models\SubscriptionTariff;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Carbon;

class SubscriptionController extends Controller
{
    /**
     * Показать страницу подписок
     */
    public function index()
    {
        $userId = Auth::id();
        $today = Carbon::today()->toDateString();

        $levels = SubscriptionLevel::where('is_active', true)
            ->orderByDesc('sort_order')
            ->get();
        
        $tariffs = SubscriptionTariff::where('is_visible', true)
            ->orderByDesc('sort_order')
            ->get();

        // Подготовка данных для JavaScript
        $subscriptionsData = $levels->map(function($level) {
            return [
                'id' => $level->id,
                'title' => $level->title,
                'link' => $level->link,
            ];
        })->values()->all();

        $tariffsData = $tariffs->map(function($tariff) {
            return [
                'id' => $tariff->id,
                'title' => $tariff->title,
                'price' => (float)$tariff->price,
                'days' => $tariff->days,
            ];
        })->values()->all();

        $activeOrders = SubscriptionOrder::query()
            ->where('user_id', $userId)
            ->where('paid', true)
            ->whereDate('date_till', '>=', $today)
            ->get(['levels', 'subscription_level_ids', 'date_till']);

        $activeByLevel = [];
        foreach ($activeOrders as $order) {
            $levelIds = $this->parseLevelIds($order->subscription_level_ids, $order->levels);
            foreach ($levelIds as $levelId) {
                if (!isset($activeByLevel[$levelId]) || $order->date_till > $activeByLevel[$levelId]['date_till']) {
                    $activeByLevel[$levelId] = [
                        'date_till' => $order->date_till,
                    ];
                }
            }
        }

        $recurringOrders = SubscriptionOrder::query()
            ->where('user_id', $userId)
            ->where('paid', false)
            ->whereNotNull('date_next_pay')
            ->whereNotNull('levels')
            ->orderBy('date_next_pay')
            ->get(['id', 'levels', 'subscription_level_ids', 'date_next_pay', 'auto']);

        $recurringByLevel = [];
        foreach ($recurringOrders as $order) {
            $levelIds = $this->parseLevelIds($order->subscription_level_ids, $order->levels);
            foreach ($levelIds as $levelId) {
                if (isset($recurringByLevel[$levelId])) {
                    continue;
                }
                $recurringByLevel[$levelId] = [
                    'order_id' => $order->id,
                    'date_next_pay' => $order->date_next_pay,
                    'auto' => (bool) $order->auto,
                ];
            }
        }

        return view('subscriptions.index', compact(
            'levels',
            'tariffs',
            'subscriptionsData',
            'tariffsData',
            'activeByLevel',
            'recurringByLevel'
        ));
    }

    public function toggleRecurring(Request $request, int $level)
    {
        $data = $request->validate([
            'enable' => ['required', 'boolean'],
        ]);

        $order = SubscriptionOrder::query()
            ->where('user_id', Auth::id())
            ->where('paid', false)
            ->whereNotNull('date_next_pay')
            ->where('levels', (string) $level)
            ->orderBy('date_next_pay')
            ->firstOrFail();

        $order->update([
            'auto' => (bool) $data['enable'],
        ]);

        return redirect()->route('subscriptions.index')
            ->with('success', $data['enable']
                ? 'Автопродление включено.'
                : 'Автопродление отключено.');
    }

    private function resolveSingleLevelId(?string $levels): ?int
    {
        if (!$levels || str_contains($levels, ',')) {
            return null;
        }

        $levelId = (int) trim($levels);
        return $levelId > 0 ? $levelId : null;
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

        $decoded = json_decode($source, true);
        if (is_array($decoded)) {
            return array_values(array_unique(array_map('intval', $decoded)));
        }

        return array_values(array_unique(array_map(
            'intval',
            array_filter(array_map('trim', explode(',', $source)))
        )));
    }
}
