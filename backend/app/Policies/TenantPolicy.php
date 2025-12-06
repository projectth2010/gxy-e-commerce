<?php

namespace App\Policies;

use App\Models\Tenant;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class TenantPolicy
{
    /**
     * Determine whether the user can update the tenant.
     */
    public function update(User $user, Tenant $tenant): bool
    {
        // Allow update if the user is associated with the tenant
        return $user->tenants->contains('id', $tenant->id);
    }
}
