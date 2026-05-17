<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\RestaurantLead;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class MenudirectLeadController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $expected = config('services.menudirect.intake_token');

        if (empty($expected)) {
            return response()->json(['error' => 'Intake endpoint not configured'], 503);
        }

        $provided = $request->bearerToken() ?: $request->header('X-Intake-Token');

        if (!$provided || !hash_equals($expected, $provided)) {
            return response()->json(['error' => 'Invalid credentials'], 401);
        }

        $validated = $request->validate([
            'restaurant_name' => 'required|string|max:255',
            'contact_name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'phone' => 'nullable|string|max:50',
            'message' => 'nullable|string|max:5000',
            'submitter_ip' => 'nullable|ip',
        ]);

        $lead = RestaurantLead::create([
            'source' => 'web_form',
            'business_name' => strip_tags($validated['restaurant_name']),
            'owner_name' => strip_tags($validated['contact_name']),
            'email' => $validated['email'],
            'phone' => $validated['phone'] ?? null,
            'notes' => isset($validated['message']) ? strip_tags($validated['message']) : null,
            'status' => 'new',
            'priority' => 'medium',
            'tags' => array_filter([
                'menudirect.ca',
                isset($validated['submitter_ip']) ? 'ip:' . $validated['submitter_ip'] : null,
            ]),
        ]);

        Log::info('MenuDirect web lead stored', [
            'lead_id' => $lead->id,
            'email' => $validated['email'],
            'restaurant' => $validated['restaurant_name'],
        ]);

        return response()->json([
            'ok' => true,
            'lead_id' => $lead->id,
        ], 201);
    }
}
