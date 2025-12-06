<?php

namespace App\Http\Controllers\Api\Center;

use App\Http\Controllers\Controller;
use App\Http\Resources\PlanResource;
use App\Models\Plan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class PlanController extends Controller
{
    /**
     * Display a listing of the plans.
     */
    public function index()
    {
        $plans = Plan::with('features')
            ->orderBy('sort_order')
            ->get();

        return PlanResource::collection($plans);
    }

    /**
     * Store a newly created plan in storage.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:50|unique:plans,code',
            'description' => 'nullable|string',
            'price' => 'required|numeric|min:0',
            'billing_cycle' => 'required|string|in:monthly,yearly',
            'trial_days' => 'required|integer|min:0',
            'is_active' => 'boolean',
            'sort_order' => 'integer',
            'features' => 'nullable|array',
            'features.*.id' => 'required|exists:features,id',
            'features.*.value' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $plan = Plan::create($request->only([
            'name', 'code', 'description', 'price', 'billing_cycle', 'trial_days', 'is_active', 'sort_order'
        ]));

        // Sync features if provided
        if ($request->has('features')) {
            $features = [];
            foreach ($request->features as $feature) {
                $features[$feature['id']] = ['value' => $feature['value']];
            }
            $plan->features()->sync($features);
        }

        return new PlanResource($plan->load('features'));
    }

    /**
     * Display the specified plan.
     */
    public function show(Plan $plan)
    {
        return new PlanResource($plan->load('features'));
    }

    /**
     * Update the specified plan in storage.
     */
    public function update(Request $request, Plan $plan)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|required|string|max:255',
            'code' => 'sometimes|required|string|max:50|unique:plans,code,' . $plan->id,
            'description' => 'nullable|string',
            'price' => 'sometimes|required|numeric|min:0',
            'billing_cycle' => 'sometimes|required|string|in:monthly,yearly',
            'trial_days' => 'sometimes|required|integer|min:0',
            'is_active' => 'sometimes|boolean',
            'sort_order' => 'sometimes|integer',
            'features' => 'nullable|array',
            'features.*.id' => 'required|exists:features,id',
            'features.*.value' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $plan->update($request->only([
            'name', 'code', 'description', 'price', 'billing_cycle', 'trial_days', 'is_active', 'sort_order'
        ]));

        // Sync features if provided
        if ($request->has('features')) {
            $features = [];
            foreach ($request->features as $feature) {
                $features[$feature['id']] = ['value' => $feature['value']];
            }
            $plan->features()->sync($features);
        }

        return new PlanResource($plan->load('features'));
    }

    /**
     * Remove the specified plan from storage.
     */
    public function destroy(Plan $plan)
    {
        // Prevent deletion if there are active subscriptions
        if ($plan->tenantAssignments()->active()->exists()) {
            return response()->json([
                'message' => 'Cannot delete plan with active subscriptions',
            ], 422);
        }

        $plan->delete();

        return response()->json(['message' => 'Plan deleted successfully']);
    }
}
