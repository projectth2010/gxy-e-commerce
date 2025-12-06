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
     * Get the subscriptions for the tenant.
     */
    public function subscriptions()
    {
        return $this->hasMany(TenantPlanAssignment::class);
    }
    
    /**
     * Get the active subscription for the tenant.
     */
    public function activeSubscription()
    {
        return $this->hasOne(TenantPlanAssignment::class)
            ->where('status', 'active')
            ->latest('starts_at');
    }
    
    /**
     * Get the route key for the model.
     *
     * @return string
     */
    public function getRouteKeyName()
    {
        return 'code';
    }
    
    /**
     * Retrieve the model for a bound value.
     *
     * @param  mixed  $value
     * @param  string|null  $field
     * @return \Illuminate\Database\Eloquent\Model|null
     */
    public function resolveRouteBinding($value, $field = null)
    {
        $logContext = [
            'value' => $value,
            'field' => $field,
            'url' => request()?->fullUrl(),
            'route_parameters' => request()?->route()?->parameters(),
            'user_id' => auth()?->id(),
            'method' => request()?->method(),
        ];
        
        \Log::info('Resolving tenant binding', $logContext);
        
        // If a field is specified, use it for the query
        if ($field) {
            $tenant = $this->where($field, $value)->first();
        } else {
            // Otherwise, try to find by code first, then by ID
            $tenant = $this->where('code', $value)
                ->orWhere('id', $value)
                ->first();
        }
        
        // If tenant is still not found, log detailed error
        if (!$tenant) {
            $errorContext = array_merge($logContext, [
                'all_tenants' => $this->select('id', 'code', 'name')->get()->toArray(),
                'request_data' => request()?->all(),
            ]);
            
            \Log::error('Tenant not found', $errorContext);
            
            // For API requests, return a JSON response
            if (request()?->wantsJson() || request()?->is('api/*')) {
                abort(response()->json([
                    'error' => 'TENANT_NOT_FOUND',
                    'message' => 'The requested tenant could not be found',
                    'requested_value' => $value,
                ], 400));
            }
            
            abort(400, 'TENANT_NOT_FOUND');
        }
        
        // Log successful resolution
        \Log::info('Successfully resolved tenant', [
            'tenant_id' => $tenant->id,
            'tenant_code' => $tenant->code,
            'user_has_access' => auth()->check() ? $tenant->users->contains(auth()->id()) : false,
        ]);
        
        return $tenant;
    }

    /**
     * The users that belong to the tenant.
     */
    public function users()
    {
        return $this->belongsToMany(User::class);
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
