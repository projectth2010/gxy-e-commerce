<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Subscription extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'subscription_plan_id',
        'stripe_subscription_id',
        'stripe_status',
        'stripe_price_id',
        'trial_ends_at',
        'ends_at',
        'canceled_at',
        'quantity',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'trial_ends_at' => 'datetime',
        'ends_at' => 'datetime',
        'canceled_at' => 'datetime',
        'quantity' => 'integer',
    ];

    /**
     * Get the user that owns the subscription.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the plan that the subscription belongs to.
     */
    public function plan(): BelongsTo
    {
        return $this->belongsTo(SubscriptionPlan::class, 'subscription_plan_id');
    }

    /**
     * Determine if the subscription is active.
     */
    public function isActive(): bool
    {
        return $this->stripe_status === 'active' || $this->onTrial();
    }

    /**
     * Determine if the subscription is on trial.
     */
    public function onTrial(): bool
    {
        return $this->trial_ends_at && $this->trial_ends_at->isFuture();
    }

    /**
     * Determine if the subscription is canceled.
     */
    public function isCanceled(): bool
    {
        return !is_null($this->canceled_at);
    }

    /**
     * Determine if the subscription has ended.
     */
    public function isEnded(): bool
    {
        return $this->ends_at && $this->ends_at->isPast();
    }
}
