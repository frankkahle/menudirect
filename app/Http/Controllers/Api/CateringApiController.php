<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Jobs\SendCateringInquiryNotificationsJob;
use App\Models\CateringInquiry;
use App\Models\RestaurantSite;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CateringApiController extends Controller
{
    /**
     * Get available catering packages for a restaurant.
     */
    public function packages(string $slug): JsonResponse
    {
        $site = RestaurantSite::where('slug', $slug)
            ->whereIn('status', ['active', 'demo'])
            ->first();

        if (!$site) {
            return response()->json(['error' => 'Restaurant not found'], 404);
        }

        if (!$site->catering_enabled || !$site->canUseCatering()) {
            return response()->json(['error' => 'Catering not available'], 404);
        }

        $packages = $site->getCateringPackages();
        $settings = $site->getCateringSettings();

        return response()->json([
            'packages' => $packages->map(function ($package) {
                return [
                    'id' => $package->id,
                    'name' => $package->name,
                    'description' => $package->description,
                    'price' => $package->formatted_price,
                    'price_raw' => (float) $package->price,
                    'price_type' => $package->price_type,
                    'min_guests' => $package->min_guests,
                    'max_guests' => $package->max_guests,
                    'lead_time_hours' => $package->lead_time_hours,
                    'includes' => $package->includes ?? [],
                    'image' => $package->image_url,
                ];
            }),
            'settings' => [
                'lead_time_hours' => $settings['lead_time_hours'],
                'min_guests' => $settings['min_guests'],
                'custom_message' => $settings['custom_message'],
            ],
        ]);
    }

    /**
     * Submit a catering inquiry.
     */
    public function store(Request $request, string $slug): JsonResponse
    {
        $site = RestaurantSite::where('slug', $slug)
            ->whereIn('status', ['active', 'demo'])
            ->first();

        if (!$site) {
            return response()->json(['error' => 'Restaurant not found'], 404);
        }

        if (!$site->catering_enabled || !$site->canUseCatering()) {
            return response()->json(['error' => 'Catering not available'], 404);
        }

        $settings = $site->getCateringSettings();
        $minLeadHours = $settings['lead_time_hours'] ?? 48;

        $validated = $request->validate([
            'customer_name' => 'required|string|max:255',
            'customer_email' => 'required|email|max:255',
            'customer_phone' => 'required|string|max:20',
            'event_date' => 'nullable|date|after:' . now()->addHours($minLeadHours)->toDateString(),
            'event_time' => 'nullable|string|max:20',
            'guest_count' => 'nullable|integer|min:1',
            'event_type' => 'nullable|string|max:50',
            'catering_package_id' => 'nullable|exists:menudirect.catering_packages,id',
            'message' => 'nullable|string|max:2000',
        ]);

        // Validate package belongs to this site
        if (!empty($validated['catering_package_id'])) {
            $packageBelongs = $site->cateringPackages()
                ->where('id', $validated['catering_package_id'])
                ->exists();

            if (!$packageBelongs) {
                return response()->json(['error' => 'Invalid package'], 422);
            }
        }

        // Validate min guests if set
        $minGuests = $settings['min_guests'] ?? 0;
        if ($minGuests > 0 && !empty($validated['guest_count']) && $validated['guest_count'] < $minGuests) {
            return response()->json([
                'error' => "Minimum {$minGuests} guests required for catering.",
            ], 422);
        }

        $inquiry = CateringInquiry::create([
            'restaurant_site_id' => $site->id,
            'catering_package_id' => $validated['catering_package_id'] ?? null,
            'status' => CateringInquiry::STATUS_NEW,
            'customer_name' => $validated['customer_name'],
            'customer_email' => $validated['customer_email'],
            'customer_phone' => $validated['customer_phone'],
            'event_date' => $validated['event_date'] ?? null,
            'event_time' => $validated['event_time'] ?? null,
            'guest_count' => $validated['guest_count'] ?? null,
            'event_type' => $validated['event_type'] ?? null,
            'message' => $validated['message'] ?? null,
            'metadata' => [
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ],
        ]);

        SendCateringInquiryNotificationsJob::dispatch($inquiry);

        return response()->json([
            'success' => true,
            'inquiry_number' => $inquiry->inquiry_number,
            'message' => 'Your catering inquiry has been submitted. We will be in touch shortly!',
        ], 201);
    }
}
