<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SubscriptionResource extends JsonResource
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
            'user_id' => $this->user_id,
            'plan' => [
                'id' => $this->plan->id,
                'name' => $this->plan->name,
                'price' => (float) $this->plan->price,
                'currency' => $this->plan->currency,
                'billing_interval' => $this->plan->billing_interval,
            ],
            'status' => $this->stripe_status,
            'trial_ends_at' => $this->trial_ends_at?->toDateTimeString(),
            'ends_at' => $this->ends_at?->toDateTimeString(),
            'is_active' => $this->isActive(),
            'is_trialing' => $this->onTrial(),
            'is_canceled' => $this->isCanceled(),
            'created_at' => $this->created_at->toDateTimeString(),
            'updated_at' => $this->updated_at->toDateTimeString(),
            'trial_ends_at' => $this->trial_ends_at,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'on_trial' => $this->on_trial,
            'active' => $this->active,
            'on_grace_period' => $this->onGracePeriod(),
            'cancelled' => !is_null($this->ends_at),
        ];
    }
}
