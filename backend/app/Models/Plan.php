<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Plan extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'code',
        'description',
        'price',
        'billing_cycle',
        'trial_days',
        'is_active',
        'sort_order',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'is_active' => 'boolean',
        'trial_days' => 'integer',
        'sort_order' => 'integer',
    ];

    public function features()
    {
        return $this->belongsToMany(Feature::class, 'plan_feature')
            ->withPivot('value')
            ->withTimestamps();
    }

    public function tenantAssignments()
    {
        return $this->hasMany(TenantPlanAssignment::class);
    }

    public function isActive(): bool
    {
        return $this->is_active === true;
    }

    public function hasFeature(string $featureCode): bool
    {
        return $this->features()->where('code', $featureCode)->exists();
    }

    public function getFeatureValue(string $featureCode, $default = null)
    {
        $feature = $this->features()->where('code', $featureCode)->first();
        
        if (!$feature) {
            return $default;
        }

        return $feature->pivot->value ?? $feature->default_value ?? $default;
    }
}
