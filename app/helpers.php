<?php

if (!function_exists('asset_versioned')) {
    /**
     * Генерирует URL для asset с версией на основе времени модификации файла
     * 
     * @param string $path Путь к файлу относительно public
     * @return string URL с версией
     */
    function asset_versioned(string $path): string
    {
        $filePath = public_path($path);
        
        if (file_exists($filePath)) {
            $version = filemtime($filePath);
            return asset($path) . '?v=' . $version;
        }
        
        return asset($path);
    }
}

if (!function_exists('site_lang')) {
    /**
     * Получает строку из словаря админки.
     *
     * Формат ключа: "group|key"
     */
    function site_lang(string $key, ?string $default = null): string
    {
        static $dictionary = null;

        if ($dictionary === null) {
            $dictionary = [];
            $dir = public_path('abc/files/languages/1/dictionary');
            if (is_dir($dir)) {
                foreach (glob($dir . '/*.php') as $file) {
                    $lang = [];
                    include $file;
                    if (!empty($lang) && is_array($lang)) {
                        foreach ($lang as $group => $items) {
                            if (!is_array($items)) {
                                continue;
                            }
                            if (!isset($dictionary[$group])) {
                                $dictionary[$group] = [];
                            }
                            $dictionary[$group] = array_merge($dictionary[$group], $items);
                        }
                    }
                }
            }
        }

        $parts = explode('|', $key, 2);
        $group = $parts[0] ?? null;
        $item = $parts[1] ?? null;

        if ($group && $item && isset($dictionary[$group][$item])) {
            return $dictionary[$group][$item];
        }

        return $default ?? $key;
    }
}

if (!function_exists('lk_parse_subscription_level_ids')) {
    /**
     * Парсит ID уровней подписки из полей заказа (subscription_level_ids или levels).
     *
     * @param mixed $subscriptionLevelIds
     * @param string|null $levels
     * @return array<int>
     */
    function lk_parse_subscription_level_ids($subscriptionLevelIds, ?string $levels = null): array
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

if (!function_exists('lk_subscription_status')) {
    /**
     * Статус подписок текущего пользователя для шапки ЛК.
     * Возвращает: есть ли активные подписки и сколько дней до ближайшего окончания.
     *
     * @return array{hasSubscriptions: bool, daysLeft: int|null}
     */
    function lk_subscription_status(): array
    {
        $userId = \Illuminate\Support\Facades\Auth::id();
        if (!$userId) {
            return ['hasSubscriptions' => false, 'daysLeft' => null];
        }

        $today = \Carbon\Carbon::today()->toDateString();
        $activeOrders = \App\Models\SubscriptionOrder::query()
            ->where('user_id', $userId)
            ->where('paid', true)
            ->whereDate('date_till', '>=', $today)
            ->get(['subscription_level_ids', 'levels', 'date_till']);

        // По каждой подписке (level_id) — максимальная date_till среди оплаченных; затем минимум из этих дат (первая истекающая).
        $maxDateTillByLevel = [];
        foreach ($activeOrders as $order) {
            if ($order->date_till === null) {
                continue;
            }
            $dateTill = $order->date_till instanceof \Carbon\Carbon
                ? $order->date_till
                : \Carbon\Carbon::parse($order->date_till);
            $levelIds = lk_parse_subscription_level_ids($order->subscription_level_ids, $order->levels);
            foreach ($levelIds as $levelId) {
                if (!isset($maxDateTillByLevel[$levelId]) || $dateTill->gt($maxDateTillByLevel[$levelId])) {
                    $maxDateTillByLevel[$levelId] = $dateTill;
                }
            }
        }

        $minDateTill = null;
        foreach ($maxDateTillByLevel as $dateTill) {
            if ($minDateTill === null || $dateTill->lt($minDateTill)) {
                $minDateTill = $dateTill;
            }
        }

        $daysLeft = null;
        if ($minDateTill !== null) {
            $todayStart = \Carbon\Carbon::today()->startOfDay();
            $endStart = $minDateTill->copy()->startOfDay();
            $daysLeft = max(0, (int) round($todayStart->diffInDays($endStart)));
        }

        return [
            'hasSubscriptions' => $activeOrders->isNotEmpty(),
            'daysLeft' => $daysLeft,
        ];
    }
}

if (!function_exists('lk_my_subscription_levels')) {
    /**
     * Уровни подписок, на которые подписан текущий пользователь (оплаченные, date_till >= сегодня).
     * Для подменю «Мои подписки» в сайдбаре ЛК.
     *
     * @return \Illuminate\Support\Collection<int, \App\Models\SubscriptionLevel>
     */
    function lk_my_subscription_levels(): \Illuminate\Support\Collection
    {
        $userId = \Illuminate\Support\Facades\Auth::id();
        if (!$userId) {
            return collect();
        }

        $today = \Carbon\Carbon::today()->toDateString();
        $activeOrders = \App\Models\SubscriptionOrder::query()
            ->where('user_id', $userId)
            ->where('paid', true)
            ->whereDate('date_till', '>=', $today)
            ->get(['subscription_level_ids', 'levels']);

        $levelIds = [];
        foreach ($activeOrders as $order) {
            $source = $order->subscription_level_ids ?: $order->levels;
            if (is_array($source)) {
                foreach (array_map('intval', $source) as $id) {
                    if ($id > 0) {
                        $levelIds[$id] = true;
                    }
                }
                continue;
            }
            if (!is_string($source) || trim($source) === '') {
                continue;
            }
            $decoded = json_decode($source, true);
            if (is_array($decoded)) {
                foreach (array_map('intval', $decoded) as $id) {
                    if ($id > 0) {
                        $levelIds[$id] = true;
                    }
                }
                continue;
            }
            foreach (array_filter(array_map('trim', explode(',', $source))) as $id) {
                $id = (int) $id;
                if ($id > 0) {
                    $levelIds[$id] = true;
                }
            }
        }
        $levelIds = array_keys($levelIds);
        if (empty($levelIds)) {
            return collect();
        }

        return \App\Models\SubscriptionLevel::query()
            ->whereIn('id', $levelIds)
            ->orderByDesc('sort_order')
            ->get(['id', 'title']);
    }
}

if (!function_exists('plural_ru')) {
    /**
     * Склонение для русского языка (1, 2-4, 5+).
     * Как в public/abc/functions/string_func.php plural().
     *
     * @param int $number число
     * @param string $str1 форма для 1 (день)
     * @param string $str2 форма для 2-4 (дня)
     * @param string $str5 форма для 5+ (дней)
     * @return string
     */
    function plural_ru(int $number, string $str1, string $str2, string $str5): string
    {
        $n = abs($number);
        return $n % 10 == 1 && $n % 100 != 11
            ? $str1
            : ($n % 10 >= 2 && $n % 10 <= 4 && ($n % 100 < 10 || $n % 100 >= 20)
                ? $str2
                : $str5);
    }
}
