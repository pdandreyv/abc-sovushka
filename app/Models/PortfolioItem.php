<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PortfolioItem extends Model
{
    protected $fillable = [
        'title',
        'badge',
        'image_thumb',
        'image_file',
        'sort_order',
        'display',
        'subscription_level_id',
        'date_from',
        'date_to',
        'user_id',
    ];

    protected $casts = [
        'display' => 'boolean',
        'sort_order' => 'integer',
        'date_from' => 'date',
        'date_to' => 'date',
    ];

    public function subscriptionLevel(): BelongsTo
    {
        return $this->belongsTo(SubscriptionLevel::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
