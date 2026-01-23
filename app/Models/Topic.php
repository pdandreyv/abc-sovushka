<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Topic extends Model
{
    protected $fillable = [
        'title',
        'keywords',
        'text',
        'display',
        'rank',
        'is_blocked',
        'subscription_level_id',
        'subject_id',
    ];

    protected $casts = [
        'display' => 'boolean',
        'rank' => 'integer',
        'is_blocked' => 'boolean',
    ];

    public function subject(): BelongsTo
    {
        return $this->belongsTo(Subject::class);
    }

    public function subscriptionLevel(): BelongsTo
    {
        return $this->belongsTo(SubscriptionLevel::class);
    }

    public function materials(): HasMany
    {
        return $this->hasMany(TopicMaterial::class);
    }
}
