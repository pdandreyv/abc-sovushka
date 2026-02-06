<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SubscriptionLevel extends Model
{
    protected $fillable = [
        'title',
        'link',
        'sort_order',
        'open',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'open' => 'boolean',
        'sort_order' => 'integer',
    ];
}
