<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PortfolioItem extends Model
{
    protected $fillable = [
        'title',
        'badge',
        'image_thumb',
        'image_file',
        'sort_order',
        'display',
    ];

    protected $casts = [
        'display' => 'boolean',
        'sort_order' => 'integer',
    ];
}
