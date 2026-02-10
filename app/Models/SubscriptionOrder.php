<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SubscriptionOrder extends Model
{
    protected $fillable = [
        'user_id',
        'yookassa_payment_id',
        'subscription_level_ids',
        'levels',
        'paid',
        'date_subscription',
        'sum_subscription',
        'sum_without_discount',
        'discount_code',
        'days',
        'date_paid',
        'date_next_pay',
        'sum_next_pay',
        'hash',
        'errors',
        'auto',
        'tariff',
        'date_till',
    ];

    protected $casts = [
        'paid' => 'boolean',
        'date_subscription' => 'date',
        'sum_subscription' => 'decimal:2',
        'sum_without_discount' => 'decimal:2',
        'days' => 'integer',
        'date_paid' => 'datetime',
        'date_next_pay' => 'date',
        'sum_next_pay' => 'decimal:2',
        'errors' => 'integer',
        'auto' => 'boolean',
        'tariff' => 'integer',
        'date_till' => 'date',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function paymentLogs(): HasMany
    {
        return $this->hasMany(SubscriptionPaymentLog::class);
    }
}
