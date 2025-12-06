<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class TenantPlanAssignment extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'tenant_id',
        'plan_id',
        'starts_at',
        'ends_at',
        'trial_ends_at',
        'status',
        'billing_cycle',
        'cancellation_reason',
        'stripe_subscription_id',
        'stripe_status',
        'stripe_price_id',
    ];

    protected $casts = [
        'starts_at' => 'datetime',
        'ends_at' => 'datetime',
        'trial_ends_at' => 'datetime',
    ];

    /**
     * The attributes that should be appended to the model's array form.
     *
     * @var array
     */
    protected $appends = ['on_trial', 'active'];

    /**
     * Get the subscription's on trial status.
     *
     * @return bool
     */
    public function getOnTrialAttribute()
    {
        return $this->onTrial();
    }

    /**
     * Get the subscription's active status.
     *
     * @return bool
     */
    public function getActiveAttribute()
    {
        return $this->active();
    }

    /**
     * Determine if the subscription is active, on trial, or within its grace period.
     *
     * @return bool
     */
    public function valid()
    {
        return $this->active() || $this->onTrial() || $this->onGracePeriod();
    }

    /**
     * Determine if the subscription is active.
     *
     * @return bool
     */
    public function active()
    {
        return $this->status === 'active' || $this->stripe_status === 'active';
    }

    /**
     * Determine if the subscription is on trial.
     *
     * @return bool
     */
    public function onTrial()
    {
        return $this->trial_ends_at && $this->trial_ends_at->isFuture();
    }

    /**
     * Determine if the subscription is within its grace period after cancellation.
     *
     * @return bool
     */
    public function onGracePeriod()
    {
        return $this->ends_at && $this->ends_at->isFuture();
    }

    // Relationships
    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }

    public function plan()
    {
        return $this->belongsTo(Plan::class);
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('status', 'active')
            ->where(function ($query) {
                $query->whereNull('ends_at')
                      ->orWhere('ends_at', '>', now());
            });
    }

    public function scopeExpired($query)
    {
        return $query->where('status', '!=' , 'expired')
            ->where('ends_at', '<=', now());
    }

    // Helper methods
    public function isActive(): bool
    {
        return $this->status === 'active' && 
               ($this->ends_at === null || $this->ends_at->isFuture());
    }

    public function isOnTrial(): bool
    {
        return $this->trial_ends_at && $this->trial_ends_at->isFuture();
    }

    public function daysUntilTrialEnds(): ?int
    {
        if (!$this->trial_ends_at) {
            return null;
        }

        return now()->diffInDays($this->trial_ends_at, false);
    }

    public function hasExpired(): bool
    {
        return $this->ends_at && $this->ends_at->isPast();
    }

    public function getFeatureValue(string $featureCode, $default = null)
    {
        return $this->plan->getFeatureValue($featureCode, $default);
    }

    public function hasFeature(string $featureCode): bool
    {
        return $this->plan->hasFeature($featureCode);
    }
}
