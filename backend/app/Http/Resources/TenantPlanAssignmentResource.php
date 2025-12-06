<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TenantPlanAssignmentResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'tenant_id' => $this->tenant_id,
            'plan_id' => $this->plan_id,
            'starts_at' => $this->starts_at?->toDateTimeString(),
            'ends_at' => $this->ends_at?->toDateTimeString(),
            'trial_ends_at' => $this->trial_ends_at?->toDateTimeString(),
            'status' => $this->status,
            'cancellation_reason' => $this->cancellation_reason,
            'billing_cycle' => $this->billing_cycle,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'is_active' => $this->isActive(),
            'is_on_trial' => $this->isOnTrial(),
            'days_until_trial_ends' => $this->daysUntilTrialEnds(),
            'has_expired' => $this->hasExpired(),
            
            // Relationships
            'tenant' => $this->whenLoaded('tenant', function () {
                return [
                    'id' => $this->tenant->id,
                    'name' => $this->tenant->name,
                    'code' => $this->tenant->code,
                    'status' => $this->tenant->status,
                ];
            }),
            
            'plan' => $this->whenLoaded('plan', function () {
                return [
                    'id' => $this->plan->id,
                    'name' => $this->plan->name,
                    'code' => $this->plan->code,
                    'price' => (float) $this->plan->price,
                    'billing_cycle' => $this->plan->billing_cycle,
                ];
            }),
            
            'features' => $this->whenLoaded('plan.features', function () {
                return $this->plan->features->map(function ($feature) {
                    return [
                        'id' => $feature->id,
                        'name' => $feature->name,
                        'code' => $feature->code,
                        'type' => $feature->type,
                        'value' => $feature->pivot->value ?? $feature->default_value,
                        'formatted_value' => $feature->getFormattedValue($feature->pivot->value ?? null),
                    ];
                });
            }),
        ];
    }
}
