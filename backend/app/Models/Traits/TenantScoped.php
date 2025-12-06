<?php

namespace App\Models\Traits;

use App\Models\Tenant;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

class TenantScoped implements Scope
{
    /**
     * Apply the scope to a given Eloquent query builder.
     */
    public function apply(Builder $builder, Model $model)
    {
        $tenant = app('currentTenant');
        
        if ($tenant) {
            $builder->where($model->getTable() . '.tenant_id', $tenant->id);
        }
    }
}

// Trait to be used in models that should be scoped to a tenant
trait BelongsToTenant
{
    /**
     * Boot the trait.
     */
    protected static function bootBelongsToTenant()
    {
        static::addGlobalScope(new TenantScoped);
        
        // When creating a new model, automatically set the tenant_id
        static::creating(function ($model) {
            if (! $model->tenant_id && $tenant = app('currentTenant')) {
                $model->tenant_id = $tenant->id;
            }
        });
    }
    
    /**
     * Get the tenant that the model belongs to.
     */
    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }
}
