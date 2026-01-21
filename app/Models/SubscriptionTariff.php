<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SubscriptionTariff extends Model
{
    protected $fillable = [
        'title',
        'price',
        'price_phrase',
        'days',
        'rating',
        'is_visible',
        'sort_order',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'days' => 'integer',
        'rating' => 'integer',
        'is_visible' => 'boolean',
        'sort_order' => 'integer',
    ];
}
