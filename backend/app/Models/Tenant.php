<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Tenant extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'code',
        'name',
        'status',
        'primary_domain',
        'plan_id',
        'config',
        'stripe_id',
        'pm_type',
        'pm_last_four',
        'trial_ends_at',
    ];

    protected $casts = [
        'config' => 'array',
        'trial_ends_at' => 'datetime',
    ];

    /**
     * The users that belong to the tenant.
     */
    public function users()
    {
        return $this->belongsToMany(User::class);
    }

    /**
     * Get the active subscription for the tenant.
     */
    public function activeSubscription()
    {
        return $this->hasOne(TenantPlanAssignment::class, 'tenant_id')
            ->whereIn('status', ['active', 'trialing'])
            ->latest();
    }

    /**
     * Determine if the tenant is on a trial subscription.
     */
    public function onTrial()
    {
        return $this->trial_ends_at && $this->trial_ends_at->isFuture();
    }

    /**
     * Get the Stripe customer ID for the tenant.
     */
    public function stripeId()
    {
        return $this->stripe_id;
    }

    // Relationships
    public function planAssignments()
    {
        return $this->hasMany(TenantPlanAssignment::class)->orderBy('created_at', 'desc');
    }

    public function currentPlanAssignment()
    {
        return $this->hasOne(TenantPlanAssignment::class)
            ->where('status', 'active')
            ->where(function ($query) {
                $query->whereNull('ends_at')
                      ->orWhere('ends_at', '>', now());
            })
            ->latest();
    }

    public function currentPlan()
    {
        return $this->currentPlanAssignment ? $this->currentPlanAssignment->plan : null;
    }

    // Feature Access
    public function hasFeature(string $featureCode): bool
    {
        $assignment = $this->currentPlanAssignment;
        return $assignment ? $assignment->hasFeature($featureCode) : false;
    }

    public function getFeatureValue(string $featureCode, $default = null)
    {
        $assignment = $this->currentPlanAssignment;
        return $assignment ? $assignment->getFeatureValue($featureCode, $default) : $default;
    }

    // Status Helpers
    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    public function isSuspended(): bool
    {
        return $this->status === 'suspended';
    }

    public function isTrial(): bool
    {
        $assignment = $this->currentPlanAssignment;
        return $assignment ? $assignment->isOnTrial() : false;
    }

    public function daysUntilTrialEnds(): ?int
    {
        $assignment = $this->currentPlanAssignment;
        return $assignment ? $assignment->daysUntilTrialEnds() : null;
    }

}
