<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Jobs\SendReservationNotificationsJob;
use App\Models\Reservation;
use App\Models\RestaurantSite;
use App\Services\ReservationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Validator;

class ReservationApiController extends Controller
{
    public function __construct(
        protected ReservationService $reservationService
    ) {}

    /**
     * Get available time slots for a date and party size.
     */
    public function availability(Request $request, string $slug): JsonResponse
    {
        $site = $this->findSite($slug);
        if (!$site) {
            return response()->json(['success' => false, 'error' => 'Restaurant not found.'], 404);
        }

        $validator = Validator::make($request->all(), [
            'date' => ['required', 'date', 'after_or_equal:today'],
            'party_size' => ['required', 'integer', 'min:1', 'max:50'],
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        $slots = $this->reservationService->getAvailableSlots(
            $site,
            $request->input('date'),
            (int) $request->input('party_size')
        );

        return response()->json([
            'success' => true,
            'date' => $request->input('date'),
            'party_size' => (int) $request->input('party_size'),
            'slots' => $slots,
        ]);
    }

    /**
     * Get which dates have availability.
     */
    public function dates(Request $request, string $slug): JsonResponse
    {
        $site = $this->findSite($slug);
        if (!$site) {
            return response()->json(['success' => false, 'error' => 'Restaurant not found.'], 404);
        }

        $partySize = (int) $request->input('party_size', 2);
        $dates = $this->reservationService->getAvailableDates($site, $partySize);

        return response()->json([
            'success' => true,
            'dates' => $dates,
        ]);
    }

    /**
     * Book a reservation.
     */
    public function store(Request $request, string $slug): JsonResponse
    {
        // Rate limiting
        $key = 'reservation:' . $request->ip();
        if (RateLimiter::tooManyAttempts($key, 10)) {
            return response()->json(['success' => false, 'error' => 'Too many requests.'], 429);
        }
        RateLimiter::hit($key, 60);

        $site = $this->findSite($slug);
        if (!$site) {
            return response()->json(['success' => false, 'error' => 'Restaurant not found.'], 404);
        }

        $settings = $site->getReservationSettings();
        if (empty($settings['enabled']) || $settings['type'] !== 'built_in' || !$site->canAcceptReservations()) {
            return response()->json(['success' => false, 'error' => 'Online reservations are not available.'], 400);
        }

        $maxParty = (int) ($settings['max_party_size'] ?? 12);

        $validator = Validator::make($request->all(), [
            'customer_name' => ['required', 'string', 'max:255'],
            'customer_email' => ['required', 'email', 'max:255'],
            'customer_phone' => ['required', 'string', 'max:50'],
            'reservation_date' => ['required', 'date', 'after_or_equal:today'],
            'reservation_time' => ['required', 'date_format:H:i'],
            'party_size' => ['required', 'integer', 'min:1', 'max:' . $maxParty],
            'special_requests' => ['nullable', 'string', 'max:1000'],
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        try {
            $reservation = $this->reservationService->createReservation($site, $validator->validated());

            // Dispatch notifications
            SendReservationNotificationsJob::dispatch($reservation, 'new_reservation');

            return response()->json([
                'success' => true,
                'reservation' => [
                    'confirmation_number' => $reservation->confirmation_number,
                    'token' => $reservation->token,
                    'status' => $reservation->status,
                    'status_label' => $reservation->status_label,
                    'date' => $reservation->formatted_date,
                    'time' => $reservation->formatted_time,
                    'party_size' => $reservation->party_size,
                ],
                'status_url' => $reservation->getStatusUrl(),
                'confirmation_message' => $settings['confirmation_message'] ?? null,
            ], 201);

        } catch (\RuntimeException $e) {
            return response()->json(['success' => false, 'error' => $e->getMessage()], 400);
        }
    }

    /**
     * Look up a reservation by token.
     */
    public function show(string $token): JsonResponse
    {
        $reservation = Reservation::where('token', $token)
            ->with('restaurantSite:id,business_name,phone,address,email')
            ->first();

        if (!$reservation) {
            return response()->json(['success' => false, 'error' => 'Reservation not found.'], 404);
        }

        return response()->json([
            'success' => true,
            'reservation' => [
                'confirmation_number' => $reservation->confirmation_number,
                'status' => $reservation->status,
                'status_label' => $reservation->status_label,
                'customer_name' => $reservation->customer_name,
                'date' => $reservation->formatted_date,
                'time' => $reservation->formatted_time,
                'party_size' => $reservation->party_size,
                'special_requests' => $reservation->special_requests,
                'cancellation_reason' => $reservation->cancellation_reason,
                'restaurant' => [
                    'name' => $reservation->restaurantSite->business_name,
                    'phone' => $reservation->restaurantSite->phone,
                    'address' => $reservation->restaurantSite->address,
                    'email' => $reservation->restaurantSite->email,
                ],
            ],
        ]);
    }

    /**
     * Cancel a reservation by token.
     */
    public function cancel(Request $request, string $token): JsonResponse
    {
        $reservation = Reservation::where('token', $token)->first();

        if (!$reservation) {
            return response()->json(['success' => false, 'error' => 'Reservation not found.'], 404);
        }

        $reason = $request->input('reason', 'Cancelled by customer');
        $result = $reservation->cancel($reason, 'customer');

        if (!$result) {
            return response()->json(['success' => false, 'error' => 'This reservation cannot be cancelled.'], 400);
        }

        // Dispatch notification
        SendReservationNotificationsJob::dispatch($reservation, 'status_update');

        return response()->json([
            'success' => true,
            'message' => 'Reservation cancelled successfully.',
            'status' => $reservation->status,
            'status_label' => $reservation->status_label,
        ]);
    }

    /**
     * Find a restaurant site by slug.
     */
    protected function findSite(string $slug): ?RestaurantSite
    {
        return RestaurantSite::where('slug', $slug)
            ->whereIn('status', [RestaurantSite::STATUS_ACTIVE, RestaurantSite::STATUS_DEMO])
            ->first();
    }
}
