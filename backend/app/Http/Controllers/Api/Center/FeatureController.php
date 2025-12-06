<?php

namespace App\Http\Controllers\Api\Center;

use App\Http\Controllers\Controller;
use App\Http\Resources\FeatureResource;
use App\Models\Feature;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class FeatureController extends Controller
{
    /**
     * Display a listing of the features.
     */
    public function index()
    {
        $features = Feature::orderBy('sort_order')
            ->get();

        return FeatureResource::collection($features);
    }

    /**
     * Store a newly created feature in storage.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:50|unique:features,code',
            'description' => 'nullable|string',
            'type' => 'required|string|in:boolean,integer,float,string,array,json',
            'default_value' => 'nullable|string',
            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $feature = Feature::create($request->only([
            'name', 'code', 'description', 'type', 'default_value', 'is_active', 'sort_order'
        ]));

        return new FeatureResource($feature);
    }

    /**
     * Display the specified feature.
     */
    public function show(Feature $feature)
    {
        return new FeatureResource($feature);
    }

    /**
     * Update the specified feature in storage.
     */
    public function update(Request $request, Feature $feature)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|required|string|max:255',
            'code' => 'sometimes|required|string|max:50|unique:features,code,' . $feature->id,
            'description' => 'nullable|string',
            'type' => 'sometimes|required|string|in:boolean,integer,float,string,array,json',
            'default_value' => 'nullable|string',
            'is_active' => 'sometimes|boolean',
            'sort_order' => 'sometimes|integer',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $feature->update($request->only([
            'name', 'code', 'description', 'type', 'default_value', 'is_active', 'sort_order'
        ]));

        return new FeatureResource($feature);
    }

    /**
     * Remove the specified feature from storage.
     */
    public function destroy(Feature $feature)
    {
        // Prevent deletion if the feature is in use by any plan
        if ($feature->plans()->exists()) {
            return response()->json([
                'message' => 'Cannot delete feature that is in use by plans',
            ], 422);
        }

        $feature->delete();

        return response()->json(['message' => 'Feature deleted successfully']);
    }
}
