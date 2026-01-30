<?php

namespace App\Http\Controllers;

use App\Models\SubscriptionLevel;
use App\Models\SubscriptionTariff;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SubscriptionController extends Controller
{
    /**
     * Показать страницу подписок
     */
    public function index()
    {
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

        return view('subscriptions.index', compact('levels', 'tariffs', 'subscriptionsData', 'tariffsData'));
    }
}
