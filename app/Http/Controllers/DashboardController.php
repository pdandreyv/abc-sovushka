<?php

namespace App\Http\Controllers;

use App\Models\Idea;
use App\Models\SubscriptionLevel;
use App\Models\SubscriptionOrder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Carbon;

class DashboardController extends Controller
{
    /**
     * Главная страница ЛК (дашборд).
     */
    public function index()
    {
        $userId = Auth::id();
        $today = Carbon::today()->toDateString();

        $activeOrders = SubscriptionOrder::query()
            ->where('user_id', $userId)
            ->where('paid', true)
            ->whereDate('date_till', '>=', $today)
            ->get(['levels', 'subscription_level_ids']);

        $activeLevelIds = [];
        foreach ($activeOrders as $order) {
            foreach ($this->parseLevelIds($order->subscription_level_ids, $order->levels) as $levelId) {
                $activeLevelIds[$levelId] = true;
            }
        }
        $activeLevelIds = array_keys($activeLevelIds);

        $subscriptionLevels = collect();
        if (!empty($activeLevelIds)) {
            $subscriptionLevels = SubscriptionLevel::query()
                ->whereIn('id', $activeLevelIds)
                ->orderByDesc('sort_order')
                ->get(['id', 'title']);
        }

        $latestIdea = Idea::query()
            ->when(\Schema::hasColumn('ideas', 'display'), fn ($q) => $q->where('display', true))
            ->orderByDesc('rank')
            ->orderByDesc('created_at')
            ->first(['id', 'title']);

        return view('dashboard', [
            'subscriptionLevels' => $subscriptionLevels,
            'latestIdea' => $latestIdea,
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
