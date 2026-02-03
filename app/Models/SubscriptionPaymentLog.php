<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SubscriptionPaymentLog extends Model
{
    protected $table = 'subscription_payments_logs';

    protected $fillable = [
        'subscription_order_id',
        'status',
        'amount',
        'message',
        'response_data',
        'payment_provider',
        'transaction_id',
        'attempted_at',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'response_data' => 'array',
        'attempted_at' => 'datetime',
    ];

    public function subscriptionOrder(): BelongsTo
    {
        return $this->belongsTo(SubscriptionOrder::class);
    }
}
