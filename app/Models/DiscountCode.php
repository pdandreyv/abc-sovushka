<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DiscountCode extends Model
{
    protected $fillable = [
        'code',
        'valid_until',
        'usage_limit',
        'subscription_level_ids',
        'discount_percent',
    ];

    protected $casts = [
        'valid_until' => 'date',
        'usage_limit' => 'integer',
        'discount_percent' => 'integer',
    ];

    /**
     * ID уровней подписок, к которым применяется код.
     *
     * @return int[]
     */
    public function getLevelIdsAttribute(): array
    {
        if (empty($this->subscription_level_ids)) {
            return [];
        }
        return array_values(array_unique(array_map('intval', explode(',', $this->subscription_level_ids))));
    }

    /**
     * Количество использований (по заказам с этим кодом).
     */
    public function getTimesUsedAttribute(): int
    {
        return SubscriptionOrder::query()
            ->where('discount_code', $this->code)
            ->count();
    }

    /**
     * Действителен ли код на дату.
     */
    public function isValidOn(?string $date = null): bool
    {
        $date = $date ?? now()->toDateString();
        return $this->valid_until && $this->valid_until->toDateString() >= $date;
    }

    /**
     * Не исчерпан ли лимит использований.
     */
    public function hasUsagesLeft(): bool
    {
        return $this->times_used < $this->usage_limit;
    }
}
