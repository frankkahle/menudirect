<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Jobs\SendOrderNotificationsJob;
use App\Models\FoodOrder;
use App\Models\FoodOrderItem;
use App\Models\MenuItem;
use App\Models\RestaurantSite;
use App\Services\DeliveryZoneService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Validator;

class FoodOrderApiController extends Controller
{
    /**
     * Create a new food order.
     *
     * @param Request $request
     * @param string $slug
     * @return JsonResponse
     */
    public function store(Request $request, string $slug): JsonResponse
    {
        // Rate limiting: 10 orders per minute per real client IP (behind Cloudflare)
        $clientIp = $request->header('CF-Connecting-IP', $request->ip());
        $key = 'food-order:' . $clientIp;
        if (RateLimiter::tooManyAttempts($key, 10)) {
            return response()->json([
                'success' => false,
                'error' => 'Too many requests. Please try again later.',
            ], 429);
        }
        RateLimiter::hit($key, 60);

        // Find the restaurant
        $site = RestaurantSite::where('slug', $slug)
            ->whereIn('status', [RestaurantSite::STATUS_ACTIVE, RestaurantSite::STATUS_DEMO])
            ->first();

        if (!$site) {
            return response()->json([
                'success' => false,
                'error' => 'Restaurant not found.',
            ], 404);
        }

        // Check if ordering is enabled
        if (!$site->canAcceptOrders()) {
            return response()->json([
                'success' => false,
                'error' => 'Online ordering is not available for this restaurant.',
            ], 400);
        }

        // Validate the request
        $validator = Validator::make($request->all(), [
            'customer_name' => ['required', 'string', 'max:255'],
            'customer_email' => ['required', 'email', 'max:255'],
            'customer_phone' => ['required', 'string', 'max:50'],
            'order_type' => ['required', 'in:pickup,delivery'],
            'is_asap' => ['boolean'],
            'scheduled_for' => ['nullable', 'date', 'after:now'],
            'delivery_address' => ['required_if:order_type,delivery', 'nullable', 'string', 'max:500'],
            'delivery_latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'delivery_longitude' => ['nullable', 'numeric', 'between:-180,180'],
            'special_instructions' => ['nullable', 'string', 'max:1000'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.menu_item_id' => ['required', 'integer', 'exists:menudirect.menu_items,id'],
            'items.*.quantity' => ['required', 'integer', 'min:1', 'max:99'],
            'items.*.special_requests' => ['nullable', 'string', 'max:255'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'error' => 'Validation failed.',
                'errors' => $validator->errors(),
            ], 422);
        }

        $data = $validator->validated();
        $orderingSettings = $site->getOrderingSettings();

        // Determine if this is an ASAP or scheduled order
        $isAsap = $data['is_asap'] ?? true;
        $scheduledFor = null;

        if (!$isAsap && !empty($data['scheduled_for'])) {
            $scheduledFor = \Carbon\Carbon::parse($data['scheduled_for']);

            // Validate scheduled time is within restaurant hours
            if (!$site->isOpenAt($scheduledFor)) {
                $hours = $site->getHoursForDate($scheduledFor);
                return response()->json([
                    'success' => false,
                    'error' => 'The restaurant is not open at the selected time.',
                    'hours' => $hours,
                ], 400);
            }
        } else {
            // ASAP order - check if restaurant is currently open (demo sites always open)
            if ($site->status !== RestaurantSite::STATUS_DEMO && !$site->isCurrentlyOpen()) {
                $nextOpen = $site->getNextOpenTime();
                $hours = $site->getHoursForDate(now());

                return response()->json([
                    'success' => false,
                    'error' => 'The restaurant is currently closed.',
                    'hours' => $hours ?: 'Closed today',
                    'next_open' => $nextOpen?->toIso8601String(),
                    'next_open_label' => $nextOpen ? ($nextOpen->isToday() ? 'Opens at ' . $nextOpen->format('g:i A') : $nextOpen->format('l \a\t g:i A')) : null,
                    'allow_scheduling' => true,
                ], 400);
            }
        }

        // Validate order type is accepted
        if ($data['order_type'] === 'delivery' && !$orderingSettings['accepts_delivery']) {
            return response()->json([
                'success' => false,
                'error' => 'Delivery is not available for this restaurant.',
            ], 400);
        }

        if ($data['order_type'] === 'pickup' && !$orderingSettings['accepts_pickup']) {
            return response()->json([
                'success' => false,
                'error' => 'Pickup is not available for this restaurant.',
            ], 400);
        }

        // Calculate totals server-side (don't trust frontend prices)
        $subtotal = 0;
        $orderItems = [];

        foreach ($data['items'] as $itemData) {
            $menuItem = MenuItem::whereHas('category', function ($query) use ($site) {
                $query->where('restaurant_site_id', $site->id)->where('active', true);
            })
                ->where('id', $itemData['menu_item_id'])
                ->where('active', true)
                ->first();

            if (!$menuItem) {
                return response()->json([
                    'success' => false,
                    'error' => 'One or more menu items are no longer available.',
                ], 400);
            }

            $itemTotal = $menuItem->price * $itemData['quantity'];
            $subtotal += $itemTotal;

            $orderItems[] = [
                'menu_item_id' => $menuItem->id,
                'name' => $menuItem->name,
                'price' => $menuItem->price,
                'quantity' => $itemData['quantity'],
                'special_requests' => $itemData['special_requests'] ?? null,
                'total' => $itemTotal,
            ];
        }

        // Check minimum order
        if ($orderingSettings['minimum_order'] > 0 && $subtotal < $orderingSettings['minimum_order']) {
            return response()->json([
                'success' => false,
                'error' => 'Minimum order amount is $' . number_format($orderingSettings['minimum_order'], 2),
            ], 400);
        }

        // Calculate tax and total
        $taxRate = $orderingSettings['tax_rate'] ?? 0;
        $taxAmount = $subtotal * $taxRate;

        // Calculate delivery fee (zone-based or flat)
        $deliveryFee = 0;
        $deliveryLat = isset($data['delivery_latitude']) ? (float) $data['delivery_latitude'] : null;
        $deliveryLng = isset($data['delivery_longitude']) ? (float) $data['delivery_longitude'] : null;
        $deliveryDistanceKm = null;
        $deliveryZoneName = null;
        $estimatedDeliveryMinutes = null;

        if ($data['order_type'] === 'delivery') {
            $deliveryZoneService = app(DeliveryZoneService::class);

            // Try zone-based delivery fee first
            if ($site->canUseDeliveryZones() && $deliveryLat && $deliveryLng && $site->latitude && $site->longitude) {
                $zoneResult = $deliveryZoneService->validateDeliveryAddress($site, $deliveryLat, $deliveryLng);

                if (!$zoneResult) {
                    return response()->json([
                        'success' => false,
                        'error' => 'This address is outside our delivery area.',
                    ], 400);
                }

                $deliveryFee = $zoneResult['delivery_fee'];
                $deliveryDistanceKm = $zoneResult['distance_km'];
                $deliveryZoneName = $zoneResult['zone_name'];
                $estimatedDeliveryMinutes = $zoneResult['estimated_delivery_minutes'];

                // Check zone-specific minimum order
                if ($zoneResult['minimum_order'] > 0 && $subtotal < $zoneResult['minimum_order']) {
                    return response()->json([
                        'success' => false,
                        'error' => 'Minimum order for this delivery area is $' . number_format($zoneResult['minimum_order'], 2),
                    ], 400);
                }
            } else {
                // Flat fee fallback
                $deliveryFee = (float) ($orderingSettings['delivery_fee'] ?? 0);
            }
        }

        $total = $subtotal + $taxAmount + $deliveryFee;

        // Estimate ready time
        $prepTime = (int) ($orderingSettings['estimated_prep_time_minutes'] ?? 30);

        if ($isAsap) {
            $estimatedReadyAt = now()->addMinutes($prepTime);
        } else {
            // For scheduled orders, ready time is the scheduled time
            $estimatedReadyAt = $scheduledFor;
        }

        // Create the order
        try {
            DB::beginTransaction();

            $order = FoodOrder::create([
                'restaurant_site_id' => $site->id,
                'status' => FoodOrder::STATUS_PENDING,
                'customer_name' => $data['customer_name'],
                'customer_email' => $data['customer_email'],
                'customer_phone' => $data['customer_phone'],
                'order_type' => $data['order_type'],
                'is_asap' => $isAsap,
                'scheduled_for' => $scheduledFor,
                'delivery_address' => $data['delivery_address'] ?? null,
                'delivery_latitude' => $deliveryLat,
                'delivery_longitude' => $deliveryLng,
                'delivery_distance_km' => $deliveryDistanceKm,
                'delivery_zone_name' => $deliveryZoneName,
                'estimated_delivery_minutes' => $estimatedDeliveryMinutes,
                'special_instructions' => $data['special_instructions'] ?? null,
                'subtotal' => $subtotal,
                'tax_amount' => $taxAmount,
                'tax_rate' => $taxRate,
                'delivery_fee' => $deliveryFee,
                'total' => $total,
                'estimated_ready_at' => $estimatedReadyAt,
                'metadata' => [
                    'ip_address' => $request->ip(),
                    'user_agent' => $request->userAgent(),
                ],
            ]);

            // Create order items
            foreach ($orderItems as $itemData) {
                $order->items()->create($itemData);
            }

            DB::commit();

            // If restaurant has online payments enabled, create a PaymentIntent via Manager API
            // Don't dispatch notifications or auto-confirm yet — wait until customer pays
            $paymentIntent = null;
            $requiresPayment = $site->online_payments_enabled
                && $site->stripe_charges_enabled
                && $site->stripe_account_id
                && $total > 0;

            if ($requiresPayment) {
                try {
                    $platformFeePercent = $site->platformFeePercent();
                    $amountCents = (int) round($total * 100);
                    $applicationFeeCents = (int) round($amountCents * $platformFeePercent / 100);

                    $managerUrl = rtrim(config('manager.api_url', 'https://api.sos-tech.ca/api/v1'), '/');
                    $managerToken = \App\Models\ApiKey::get('manager', 'api_token') ?? config('manager.api_token', '');

                    $resp = \Illuminate\Support\Facades\Http::withToken($managerToken)
                        ->acceptJson()
                        ->timeout(15)
                        ->post($managerUrl . '/payments/connect/charges', [
                            'account_id' => $site->stripe_account_id,
                            'amount' => $amountCents,
                            'currency' => 'CAD',
                            'application_fee' => $applicationFeeCents,
                            'metadata' => [
                                'order_number' => $order->order_number,
                                'order_id' => (string) $order->id,
                                'restaurant_slug' => $site->slug,
                            ],
                            'source' => 'menudirect',
                        ]);

                    if ($resp->successful()) {
                        $pi = $resp->json();
                        $order->update([
                            'payment_status' => 'pending',
                            'payment_intent_id' => $pi['payment_intent_id'],
                            'platform_fee_cents' => $applicationFeeCents,
                        ]);
                        $paymentIntent = $pi;
                    } else {
                        \Log::error("Failed to create PaymentIntent for order {$order->id}", [
                            'response' => $resp->json(),
                        ]);
                        // Order is still created — falls back to pay-at-pickup flow
                        $order->update(['payment_status' => 'unpaid']);
                    }
                } catch (\Exception $e) {
                    \Log::error("PaymentIntent creation exception for order {$order->id}: {$e->getMessage()}");
                    $order->update(['payment_status' => 'unpaid']);
                }
            }

            // For unpaid orders, dispatch notifications immediately. For pending-payment
            // orders, we wait until the payment is confirmed (via webhook or polling).
            if ($order->payment_status !== 'pending') {
                SendOrderNotificationsJob::dispatch($order);

                if ($orderingSettings['auto_confirm']) {
                    $order->confirm($prepTime);
                }
            }

            $response = [
                'success' => true,
                'order' => [
                    'order_number' => $order->order_number,
                    'token' => $order->token,
                    'status' => $order->status,
                    'status_label' => $order->status_label,
                    'total' => $order->formatted_total,
                    'estimated_ready_at' => $order->estimated_ready_at?->toIso8601String(),
                    'payment_status' => $order->payment_status,
                ],
                'tracking_url' => $order->getTrackingUrl(),
            ];

            // Include payment details if online payment is required
            if ($paymentIntent) {
                $response['payment'] = [
                    'required' => true,
                    'client_secret' => $paymentIntent['client_secret'],
                    'publishable_key' => config('services.stripe.publishable_key'),
                    'connected_account_id' => $site->stripe_account_id,
                    'platform_fee_cents' => $applicationFeeCents ?? 0,
                    'amount_cents' => $amountCents ?? 0,
                ];
            } else {
                $response['payment'] = ['required' => false];
            }

            return response()->json($response, 201);

        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'error' => 'Failed to create order. Please try again.',
            ], 500);
        }
    }

    /**
     * Get order details by tracking token.
     *
     * @param string $token
     * @return JsonResponse
     */
    public function show(string $token): JsonResponse
    {
        $order = FoodOrder::where('token', $token)
            ->with(['items', 'restaurantSite:id,business_name,phone,address'])
            ->first();

        if (!$order) {
            return response()->json([
                'success' => false,
                'error' => 'Order not found.',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'order' => [
                'order_number' => $order->order_number,
                'status' => $order->status,
                'status_label' => $order->status_label,
                'payment_status' => $order->payment_status,
                'order_type' => $order->order_type,
                'order_type_label' => $order->order_type_label,
                'customer_name' => $order->customer_name,
                'delivery_address' => $order->delivery_address,
                'special_instructions' => $order->special_instructions,
                'items' => $order->items->map(function ($item) {
                    return [
                        'name' => $item->name,
                        'price' => $item->formatted_price,
                        'quantity' => $item->quantity,
                        'special_requests' => $item->special_requests,
                        'total' => $item->formatted_total,
                    ];
                }),
                'subtotal' => $order->formatted_subtotal,
                'tax_amount' => $order->formatted_tax,
                'delivery_fee' => $order->delivery_fee > 0 ? $order->formatted_delivery_fee : null,
                'total' => $order->formatted_total,
                'estimated_ready_at' => $order->estimated_ready_at?->toIso8601String(),
                'confirmed_at' => $order->confirmed_at?->toIso8601String(),
                'ready_at' => $order->ready_at?->toIso8601String(),
                'completed_at' => $order->completed_at?->toIso8601String(),
                'cancelled_at' => $order->cancelled_at?->toIso8601String(),
                'cancellation_reason' => $order->cancellation_reason,
                'created_at' => $order->created_at->toIso8601String(),
                'restaurant' => [
                    'name' => $order->restaurantSite->business_name,
                    'phone' => $order->restaurantSite->phone,
                    'address' => $order->restaurantSite->address,
                ],
            ],
        ]);
    }

    /**
     * Get just the order status by tracking token.
     *
     * @param string $token
     * @return JsonResponse
     */
    public function status(string $token): JsonResponse
    {
        $order = FoodOrder::where('token', $token)->first();

        if (!$order) {
            return response()->json([
                'success' => false,
                'error' => 'Order not found.',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'status' => $order->status,
            'status_label' => $order->status_label,
            'payment_status' => $order->payment_status,
            'estimated_ready_at' => $order->estimated_ready_at?->toIso8601String(),
            'ready_at' => $order->ready_at?->toIso8601String(),
            'cancelled_at' => $order->cancelled_at?->toIso8601String(),
        ]);
    }

    /**
     * Get scheduling info (hours, open status, time slots).
     *
     * @param string $slug
     * @return JsonResponse
     */
    public function scheduling(string $slug): JsonResponse
    {
        $site = RestaurantSite::where('slug', $slug)
            ->whereIn('status', [RestaurantSite::STATUS_ACTIVE, RestaurantSite::STATUS_DEMO])
            ->first();

        if (!$site) {
            return response()->json([
                'success' => false,
                'error' => 'Restaurant not found.',
            ], 404);
        }

        $isCurrentlyOpen = ($site->status === RestaurantSite::STATUS_DEMO) ? true : $site->isCurrentlyOpen();
        $nextOpenTime = !$isCurrentlyOpen ? $site->getNextOpenTime() : null;

        return response()->json([
            'success' => true,
            'is_open' => $isCurrentlyOpen,
            'today_hours' => $site->getHoursForDate(now()),
            'next_open_label' => $nextOpenTime
                ? ($nextOpenTime->isToday()
                    ? 'Opens at ' . $nextOpenTime->format('g:i A')
                    : $nextOpenTime->format('l \a\t g:i A'))
                : null,
            'slots' => $site->getSchedulingSlots(7, 30),
        ]);
    }

    /**
     * Lookup customer info from previous orders by phone number.
     *
     * @param Request $request
     * @param string $slug
     * @return JsonResponse
     */
    public function lookupCustomer(Request $request, string $slug): JsonResponse
    {
        // Rate limiting: 5 lookups per minute per IP (tightened from 30)
        $clientIp = $request->header('CF-Connecting-IP', $request->ip());
        $key = 'customer-lookup:' . $clientIp;
        if (RateLimiter::tooManyAttempts($key, 5)) {
            return response()->json([
                'success' => false,
                'error' => 'Too many requests. Please try again later.',
            ], 429);
        }
        RateLimiter::hit($key, 60);

        // Validate input — require minimum 10 digits to prevent fishing with short numbers
        $validator = Validator::make($request->all(), [
            'phone' => ['required', 'string', 'min:10', 'max:20'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'error' => 'Please enter a valid phone number.',
            ], 422);
        }

        // Find the restaurant
        $site = RestaurantSite::where('slug', $slug)
            ->whereIn('status', [RestaurantSite::STATUS_ACTIVE, RestaurantSite::STATUS_DEMO])
            ->first();

        if (!$site) {
            return response()->json([
                'success' => false,
                'error' => 'Restaurant not found.',
            ], 404);
        }

        // Ordering must be enabled on the site
        if (!$site->ordering_enabled) {
            return response()->json([
                'success' => false,
                'error' => 'Ordering is not enabled for this restaurant.',
            ], 403);
        }

        // Normalize phone number for search (remove non-digits)
        $phone = $request->input('phone');
        $phoneDigits = preg_replace('/[^0-9]/', '', $phone);

        // Require at least 10 digits after normalization
        if (strlen($phoneDigits) < 10) {
            return response()->json([
                'success' => false,
                'error' => 'Please enter a valid phone number.',
            ], 422);
        }

        // Find the most recent completed order from this phone at this restaurant
        // Use exact digit match (not LIKE with wildcards) to prevent partial matches
        $order = FoodOrder::where('restaurant_site_id', $site->id)
            ->whereRaw("REPLACE(REPLACE(REPLACE(REPLACE(customer_phone, '-', ''), '(', ''), ')', ''), ' ', '') LIKE ?", ['%' . $phoneDigits])
            ->whereIn('status', [
                FoodOrder::STATUS_COMPLETED,
                FoodOrder::STATUS_CONFIRMED,
                FoodOrder::STATUS_READY,
                FoodOrder::STATUS_PREPARING,
            ])
            ->orderBy('created_at', 'desc')
            ->first();

        if (!$order) {
            return response()->json([
                'success' => false,
                'found' => false,
                'message' => 'No previous orders found with this phone number.',
            ]);
        }

        // Return only what the checkout form needs — redact email to partial
        $email = $order->customer_email;
        $maskedEmail = null;
        if ($email) {
            $parts = explode('@', $email);
            $maskedEmail = substr($parts[0], 0, 2) . '***@' . ($parts[1] ?? '***');
        }

        return response()->json([
            'success' => true,
            'found' => true,
            'customer' => [
                'name' => $order->customer_name,
                'email' => $maskedEmail,
                'phone' => $order->customer_phone,
            ],
            'last_order' => [
                'date' => $order->created_at->diffForHumans(),
                'order_type' => $order->order_type,
            ],
        ]);
    }

    /**
     * Validate a delivery address against the restaurant's delivery zones.
     */
    public function validateDeliveryAddress(Request $request, string $slug, DeliveryZoneService $deliveryZoneService): JsonResponse
    {
        $site = RestaurantSite::where('slug', $slug)
            ->whereIn('status', [RestaurantSite::STATUS_ACTIVE, RestaurantSite::STATUS_DEMO])
            ->first();

        if (!$site) {
            return response()->json(['success' => false, 'error' => 'Restaurant not found.'], 404);
        }

        $validator = Validator::make($request->all(), [
            'latitude' => ['required', 'numeric', 'between:-90,90'],
            'longitude' => ['required', 'numeric', 'between:-180,180'],
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'error' => 'Invalid coordinates.'], 422);
        }

        $lat = (float) $request->input('latitude');
        $lng = (float) $request->input('longitude');

        // Check if site has delivery zones configured
        if (!$site->canUseDeliveryZones() || !$site->latitude || !$site->longitude) {
            // Fall back to flat fee
            $settings = $site->getOrderingSettings();
            return response()->json([
                'success' => true,
                'in_range' => true,
                'delivery_fee' => (float) ($settings['delivery_fee'] ?? 0),
                'minimum_order' => (float) ($settings['minimum_order'] ?? 0),
                'estimated_delivery_minutes' => null,
                'zone_name' => null,
                'distance_km' => null,
            ]);
        }

        $result = $deliveryZoneService->validateDeliveryAddress($site, $lat, $lng);

        if (!$result) {
            $maxZone = $site->deliveryZones()->active()->ordered()->orderByDesc('radius_km')->first();
            return response()->json([
                'success' => true,
                'in_range' => false,
                'error' => 'This address is outside our delivery area.',
                'max_radius_km' => $maxZone ? (float) $maxZone->radius_km : null,
            ]);
        }

        return response()->json([
            'success' => true,
            'in_range' => true,
            'delivery_fee' => $result['delivery_fee'],
            'minimum_order' => $result['minimum_order'],
            'estimated_delivery_minutes' => $result['estimated_delivery_minutes'],
            'zone_name' => $result['zone_name'],
            'distance_km' => $result['distance_km'],
        ]);
    }
}
