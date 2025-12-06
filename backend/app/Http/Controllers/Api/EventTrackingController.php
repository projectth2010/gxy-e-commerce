<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\TrackingEvent;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class EventTrackingController extends Controller
{
    /**
     * Store a new tracking event.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request)
    {
        $validatedData = $request->validate([
            'session_id' => 'required|string|max:255',
            'event_name' => 'required|string|max:255',
            'payload' => 'sometimes|array'
        ]);

        try {
            $event = TrackingEvent::create($validatedData);

            // Log for debugging purposes. Can be removed in production.
            Log::info('Tracking event stored:', ['event_id' => $event->id, 'tenant_id' => $event->tenant_id]);

            return response()->json(['message' => 'Event tracked successfully'], 201);

        } catch (\Exception $e) {
            Log::error('Failed to store tracking event:', ['error' => $e->getMessage()]);
            return response()->json(['message' => 'Failed to track event'], 500);
        }
    }
}
