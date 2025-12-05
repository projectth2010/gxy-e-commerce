<?php

namespace App\Core\Tenancy;

use App\Models\Tenant;
use Illuminate\Http\Request;

class TenantResolver
{
    public function resolve(Request $request): ?Tenant
    {
        $code = $request->header('X-Tenant-Key');

        if (! $code) {
            return null;
        }

        return Tenant::where('code', $code)
            ->where('status', '!=', 'terminated')
            ->first();
    }
}
