<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\DemoSession;
use App\Services\DemoSandboxService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DemoController extends Controller
{
    public function create(Request $request, DemoSandboxService $service): JsonResponse
    {
        if (!config('demo.enabled', true)) {
            return response()->json(['error' => 'Demo sandbox is currently disabled.'], 503);
        }

        $validated = $request->validate([
            'email' => 'required|email|max:255',
            'name' => 'nullable|string|max:255',
        ]);

        try {
            $session = $service->createSession(
                $validated['email'],
                $validated['name'] ?? null,
                $request->ip()
            );

            $dashboardUrl = url('/client/restaurant/' . $session->restaurant_site_id) . '?demo_token=' . $session->token;

            return response()->json([
                'success' => true,
                'token' => $session->token,
                'expires_at' => $session->expires_at->toIso8601String(),
                'dashboard_url' => $dashboardUrl,
            ]);
        } catch (\Illuminate\Http\Exceptions\HttpResponseException $e) {
            throw $e;
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Demo session creation failed', [
                'error' => $e->getMessage(),
                'file' => $e->getFile() . ':' . $e->getLine(),
                'email' => $validated['email'],
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Failed to create demo session. Please try again.',
            ], 500);
        }
    }

    public function status(string $token): JsonResponse
    {
        $session = DemoSession::where('token', $token)->first();

        if (!$session) {
            return response()->json(['error' => 'Session not found.'], 404);
        }

        return response()->json([
            'active' => $session->isActive(),
            'expires_at' => $session->expires_at->toIso8601String(),
            'remaining_minutes' => $session->getRemainingMinutes(),
        ]);
    }
}
