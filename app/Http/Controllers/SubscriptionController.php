<?php

namespace App\Http\Controllers;

use App\Models\DiscountCode;
use App\Models\SubscriptionLevel;
use App\Models\SubscriptionOrder;
use App\Models\SubscriptionTariff;
use Illuminate\Http\JsonResponse;
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
            ->where('display', true)
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
            ->get(['levels', 'subscription_level_ids', 'date_till', 'tariff']);

        $activeByLevel = [];
        foreach ($activeOrders as $order) {
            $levelIds = $this->parseLevelIds($order->subscription_level_ids, $order->levels);
            foreach ($levelIds as $levelId) {
                if (!isset($activeByLevel[$levelId]) || $order->date_till > $activeByLevel[$levelId]['date_till']) {
                    $activeByLevel[$levelId] = [
                        'date_till' => $order->date_till,
                        'tariff_id' => $order->tariff ? (int) $order->tariff : null,
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
            ->get(['id', 'levels', 'subscription_level_ids', 'date_next_pay', 'sum_next_pay', 'auto', 'card_last4']);

        $recurringByLevel = [];
        $recurringMultiLevel = [];
        $levelsById = $levels->keyBy('id');

        foreach ($recurringOrders as $order) {
            $levelIds = $this->parseLevelIds($order->subscription_level_ids, $order->levels);
            if (count($levelIds) > 1) {
                $titles = [];
                foreach ($levelIds as $lid) {
                    $l = $levelsById->get($lid);
                    $titles[] = $l ? $l->title : (string) $lid;
                }
                $recurringMultiLevel[] = [
                    'order_id' => $order->id,
                    'level_titles' => $titles,
                    'sum_next_pay' => (float) ($order->sum_next_pay ?? 0),
                    'date_next_pay' => $order->date_next_pay,
                    'auto' => (bool) $order->auto,
                    'card_last4' => $order->card_last4,
                    'first_level_id' => (int) $levelIds[0],
                ];
                continue;
            }
            foreach ($levelIds as $levelId) {
                if (isset($recurringByLevel[$levelId])) {
                    continue;
                }
                $recurringByLevel[$levelId] = [
                    'order_id' => $order->id,
                    'date_next_pay' => $order->date_next_pay,
                    'auto' => (bool) $order->auto,
                    'card_last4' => $order->card_last4,
                ];
            }
        }

        return view('subscriptions.index', compact(
            'levels',
            'tariffs',
            'subscriptionsData',
            'tariffsData',
            'activeByLevel',
            'recurringByLevel',
            'recurringMultiLevel'
        ));
    }

    /**
     * Проверка и применение промокода (AJAX).
     * Один пользователь может использовать код только один раз.
     */
    public function applyCode(Request $request): JsonResponse
    {
        $request->validate([
            'code' => ['required', 'string', 'max:64'],
            'level_ids' => ['required', 'array', 'min:1'],
            'level_ids.*' => ['integer'],
        ]);

        $codeRaw = trim($request->input('code'));
        $levelIds = array_values(array_unique(array_map('intval', $request->input('level_ids'))));

        $promo = DiscountCode::query()
            ->whereRaw('LOWER(code) = ?', [mb_strtolower($codeRaw)])
            ->where('display', true)
            ->first();

        if (! $promo) {
            return response()->json([
                'success' => false,
                'error' => 'code_invalid',
                'message' => 'Код не найден или недействителен.',
            ]);
        }

        if (! $promo->isValidOn(now()->toDateString())) {
            return response()->json([
                'success' => false,
                'error' => 'code_expired',
                'message' => 'Срок действия кода истёк.',
            ]);
        }

        if (! $promo->hasUsagesLeft()) {
            return response()->json([
                'success' => false,
                'error' => 'code_exhausted',
                'message' => 'Код исчерпан (достигнут лимит использований).',
            ]);
        }

        $userUsed = SubscriptionOrder::query()
            ->where('user_id', Auth::id())
            ->where('discount_code', $promo->code)
            ->exists();

        if ($userUsed) {
            return response()->json([
                'success' => false,
                'error' => 'code_used',
                'message' => 'Вы уже использовали этот код.',
            ]);
        }

        $codeLevelIds = $promo->level_ids;
        if (empty($codeLevelIds)) {
            return response()->json([
                'success' => false,
                'error' => 'code_invalid',
                'message' => 'Код не привязан ни к одному уровню подписки.',
            ]);
        }

        $intersection = array_intersect($levelIds, $codeLevelIds);
        if (empty($intersection)) {
            $levelTitles = SubscriptionLevel::query()
                ->whereIn('id', $codeLevelIds)
                ->orderBy('sort_order')
                ->pluck('title')
                ->toArray();

            return response()->json([
                'success' => false,
                'error' => 'code_no_match',
                'message' => 'Код не подходит к выбранным подпискам.',
                'level_titles' => $levelTitles,
            ]);
        }

        $levelTitles = SubscriptionLevel::query()
            ->whereIn('id', $intersection)
            ->orderBy('sort_order')
            ->pluck('title')
            ->toArray();

        return response()->json([
            'success' => true,
            'discount_percent' => $promo->discount_percent,
            'level_titles' => $levelTitles,
            'message' => sprintf(
                'Применена скидка %d%% на подписки: %s.',
                $promo->discount_percent,
                implode(', ', $levelTitles)
            ),
        ]);
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
            ->where(function ($q) use ($level) {
                $q->where('levels', (string) $level)
                    ->orWhereRaw('FIND_IN_SET(?, levels)', [$level]);
            })
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
