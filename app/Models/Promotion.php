<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Promotion extends Model
{
    protected $fillable = [
        'user_id',
        'subscription_level_ids',
        'tariff_id',
        'special_price',
        'free_days',
        'used',
        'used_at',
    ];

    protected $casts = [
        'special_price' => 'decimal:2',
        'free_days' => 'integer',
        'used' => 'boolean',
        'used_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function tariff(): BelongsTo
    {
        return $this->belongsTo(SubscriptionTariff::class, 'tariff_id');
    }

    /** ID уровней подписок в виде массива */
    public function getLevelIds(): array
    {
        if (empty($this->subscription_level_ids)) {
            return [];
        }
        return array_map('intval', array_filter(explode(',', $this->subscription_level_ids)));
    }
}
