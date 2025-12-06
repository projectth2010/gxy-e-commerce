<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PlanResource extends JsonResource
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
            'name' => $this->name,
            'code' => $this->code,
            'description' => $this->description,
            'price' => (float) $this->price,
            'billing_cycle' => $this->billing_cycle,
            'trial_days' => (int) $this->trial_days,
            'is_active' => (bool) $this->is_active,
            'sort_order' => (int) $this->sort_order,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'features' => $this->whenLoaded('features', function () {
                return $this->features->map(function ($feature) {
                    return [
                        'id' => $feature->id,
                        'name' => $feature->name,
                        'code' => $feature->code,
                        'description' => $feature->description,
                        'type' => $feature->type,
                        'default_value' => $feature->default_value,
                        'value' => $feature->pivot->value ?? $feature->default_value,
                    ];
                });
            }),
        ];
    }
}
