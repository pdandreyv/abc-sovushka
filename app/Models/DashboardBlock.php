<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DashboardBlock extends Model
{
    protected $fillable = [
        'type',
        'title',
        'image',
        'url',
        'text',
        'rank',
        'display',
    ];

    protected $casts = [
        'display' => 'boolean',
    ];

    public const TYPE_BANNER = 'banner';
    public const TYPE_ANNOUNCEMENT = 'announcement';

    public function isBanner(): bool
    {
        return $this->type === self::TYPE_BANNER;
    }

    public function isAnnouncement(): bool
    {
        return $this->type === self::TYPE_ANNOUNCEMENT;
    }
}
