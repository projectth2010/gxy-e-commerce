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
            'plan' => new PlanResource($this->whenLoaded('plan')),
            'status' => $this->status,
            'stripe_status' => $this->stripe_status,
            'billing_cycle' => $this->billing_cycle,
            'starts_at' => $this->starts_at,
            'ends_at' => $this->ends_at,
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
