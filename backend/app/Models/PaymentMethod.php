<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PaymentMethod extends Model
{
    protected $fillable = [
        'user_id',
        'stripe_payment_method_id',
        'card_type',
        'last_four',
        'exp_month',
        'exp_year',
        'card_expires_at',
        'is_default',
    ];

    protected $casts = [
        'is_default' => 'boolean',
        'card_expires_at' => 'datetime',
    ];

    /**
     * Get the user that owns the payment method.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
