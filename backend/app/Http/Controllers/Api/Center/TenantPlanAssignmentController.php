<?php

namespace App\Http\Controllers\Api\Center;

use App\Http\Controllers\Controller;
use App\Http\Resources\TenantPlanAssignmentResource;
use App\Models\Plan;
use App\Models\Tenant;
use App\Models\TenantPlanAssignment;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class TenantPlanAssignmentController extends Controller
{
    /**
     * Display a listing of the tenant plan assignments.
     */
    public function index(Request $request)
    {
        $query = TenantPlanAssignment::with(['tenant', 'plan'])
            ->latest();

        if ($request->has('tenant_id')) {
            $query->where('tenant_id', $request->tenant_id);
        }

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        $assignments = $query->paginate($request->per_page ?? 20);

        return TenantPlanAssignmentResource::collection($assignments);
    }

    /**
     * Assign a plan to a tenant.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'tenant_id' => 'required|exists:tenants,id',
            'plan_id' => 'required|exists:plans,id',
            'starts_at' => 'nullable|date',
            'ends_at' => 'nullable|date|after:starts_at',
            'trial_ends_at' => 'nullable|date|after:now',
            'status' => 'required|in:pending,active,canceled,expired',
            'billing_cycle' => 'required|in:monthly,yearly',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $tenant = Tenant::findOrFail($request->tenant_id);
        $plan = Plan::findOrFail($request->plan_id);

        // End current active assignment if exists
        $currentAssignment = $tenant->currentPlanAssignment;
        if ($currentAssignment) {
            $currentAssignment->update([
                'status' => 'canceled',
                'ends_at' => now(),
            ]);
        }

        // Create new assignment
        $assignment = TenantPlanAssignment::create([
            'tenant_id' => $tenant->id,
            'plan_id' => $plan->id,
            'starts_at' => $request->starts_at ?? now(),
            'ends_at' => $request->ends_at,
            'trial_ends_at' => $request->trial_ends_at ?? ($plan->trial_days > 0 
                ? now()->addDays($plan->trial_days) 
                : null),
            'status' => $request->status,
            'billing_cycle' => $request->billing_cycle,
        ]);

        // Update tenant status if needed
        if ($assignment->status === 'active') {
            $tenant->update(['status' => 'active']);
        }

        return new TenantPlanAssignmentResource($assignment->load(['tenant', 'plan']));
    }

    /**
     * Display the specified tenant plan assignment.
     */
    public function show(TenantPlanAssignment $assignment)
    {
        return new TenantPlanAssignmentResource($assignment->load(['tenant', 'plan']));
    }

    /**
     * Update the specified tenant plan assignment.
     */
    public function update(Request $request, TenantPlanAssignment $assignment)
    {
        $validator = Validator::make($request->all(), [
            'status' => 'sometimes|in:pending,active,canceled,expired',
            'ends_at' => 'nullable|date|after:now',
            'trial_ends_at' => 'nullable|date|after:now',
            'cancellation_reason' => 'nullable|string|max:1000',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $assignment->update($request->only([
            'status', 'ends_at', 'trial_ends_at', 'cancellation_reason'
        ]));

        // Update tenant status if assignment is being canceled
        if ($request->has('status') && $request->status === 'canceled') {
            $assignment->tenant->update(['status' => 'suspended']);
        }

        return new TenantPlanAssignmentResource($assignment->load(['tenant', 'plan']));
    }

    /**
     * Cancel the specified tenant plan assignment.
     */
    public function cancel(TenantPlanAssignment $assignment)
    {
        if ($assignment->status === 'canceled') {
            return response()->json([
                'message' => 'Assignment is already canceled',
            ], 422);
        }

        $assignment->update([
            'status' => 'canceled',
            'ends_at' => now(),
            'cancellation_reason' => request('reason', 'Canceled by admin')
        ]);

        // Update tenant status
        $assignment->tenant->update(['status' => 'suspended']);

        return new TenantPlanAssignmentResource($assignment->load(['tenant', 'plan']));
    }
}
