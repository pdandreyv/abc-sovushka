<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Subject extends Model
{
    protected $fillable = [
        'subscription_level_id',
        'title',
        'link',
        'rating',
        'display',
    ];

    protected $casts = [
        'rating' => 'integer',
        'display' => 'boolean',
    ];

    public function subscriptionLevel(): BelongsTo
    {
        return $this->belongsTo(SubscriptionLevel::class);
    }

    public function topics(): HasMany
    {
        return $this->hasMany(Topic::class);
    }
}
