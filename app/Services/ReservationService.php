<?php

namespace App\Services;

use App\Models\Reservation;
use App\Models\RestaurantSite;
use Carbon\Carbon;

class ReservationService
{
    /**
     * Get available time slots for a given date and party size.
     */
    public function getAvailableSlots(RestaurantSite $site, string $date, int $partySize): array
    {
        $settings = $site->getReservationSettings();
        $dateObj = Carbon::parse($date);

        // Check if date is blocked
        $blockedDates = $settings['blocked_dates'] ?? [];
        if (in_array($dateObj->format('Y-m-d'), $blockedDates)) {
            return [];
        }

        // Get restaurant hours for the date
        $hours = $site->getHoursForDate($dateObj);
        if (!$hours || strtolower($hours) === 'closed') {
            return [];
        }

        $parsed = $site->parseHoursString($hours);
        if (!$parsed) {
            return [];
        }

        $slotDuration = (int) ($settings['slot_duration_minutes'] ?? 30);
        $maxCovers = (int) ($settings['max_covers_per_slot'] ?? 40);
        $reservationDuration = (int) ($settings['default_duration_minutes'] ?? 90);
        $minAdvanceHours = (int) ($settings['min_advance_hours'] ?? 2);

        $openTime = $dateObj->copy()->setTimeFromTimeString($parsed['open']);
        $closeTime = $dateObj->copy()->setTimeFromTimeString($parsed['close']);

        // Stop accepting reservations 1 hour before close
        $lastSlot = $closeTime->copy()->subHour();

        // For today, enforce minimum advance time
        $now = now();
        if ($dateObj->isToday()) {
            $earliestSlot = $now->copy()->addHours($minAdvanceHours)->ceil($slotDuration . ' minutes');
            if ($earliestSlot->gt($openTime)) {
                $openTime = $earliestSlot;
            }
        }

        // Get existing reservations for this date
        $existingReservations = $site->reservations()
            ->forDate($dateObj)
            ->active()
            ->get();

        // Generate slots
        $slots = [];
        $slotTime = $openTime->copy();

        while ($slotTime->lte($lastSlot)) {
            $slotKey = $slotTime->format('H:i');

            // Calculate covers used at this slot time
            $coversUsed = $this->getCoversAtTime($existingReservations, $slotTime, $slotDuration, $reservationDuration);
            $availableCovers = $maxCovers - $coversUsed;
            $isAvailable = $availableCovers >= $partySize;

            $slots[] = [
                'time' => $slotKey,
                'label' => $slotTime->format('g:i A'),
                'available' => $isAvailable,
                'covers_available' => max(0, $availableCovers),
            ];

            $slotTime->addMinutes($slotDuration);
        }

        return $slots;
    }

    /**
     * Get which dates have availability in a date range.
     */
    public function getAvailableDates(RestaurantSite $site, int $partySize, int $days = 30): array
    {
        $dates = [];
        $settings = $site->getReservationSettings();
        $advanceDays = (int) ($settings['advance_booking_days'] ?? 30);
        $days = min($days, $advanceDays);

        for ($i = 0; $i <= $days; $i++) {
            $date = now()->addDays($i);
            $dateString = $date->format('Y-m-d');

            $slots = $this->getAvailableSlots($site, $dateString, $partySize);
            $hasAvailability = collect($slots)->contains('available', true);

            if ($hasAvailability) {
                $dates[] = [
                    'date' => $dateString,
                    'label' => $i === 0 ? 'Today' : ($i === 1 ? 'Tomorrow' : $date->format('l, M j')),
                    'day_name' => $date->format('l'),
                    'available_slots' => collect($slots)->where('available', true)->count(),
                ];
            }
        }

        return $dates;
    }

    /**
     * Create a new reservation, re-checking availability.
     */
    public function createReservation(RestaurantSite $site, array $data): Reservation
    {
        $settings = $site->getReservationSettings();

        // Verify the slot is still available
        $slots = $this->getAvailableSlots($site, $data['reservation_date'], $data['party_size']);
        $requestedTime = Carbon::parse($data['reservation_time'])->format('H:i');

        $slot = collect($slots)->firstWhere('time', $requestedTime);

        if (!$slot || !$slot['available']) {
            throw new \RuntimeException('This time slot is no longer available.');
        }

        $status = !empty($settings['auto_confirm'])
            ? Reservation::STATUS_CONFIRMED
            : Reservation::STATUS_PENDING;

        $reservation = Reservation::create([
            'restaurant_site_id' => $site->id,
            'status' => $status,
            'customer_name' => $data['customer_name'],
            'customer_email' => $data['customer_email'],
            'customer_phone' => $data['customer_phone'],
            'reservation_date' => $data['reservation_date'],
            'reservation_time' => $data['reservation_time'],
            'party_size' => $data['party_size'],
            'duration_minutes' => (int) ($settings['default_duration_minutes'] ?? 90),
            'special_requests' => $data['special_requests'] ?? null,
            'confirmed_at' => $status === Reservation::STATUS_CONFIRMED ? now() : null,
        ]);

        return $reservation;
    }

    /**
     * Calculate the number of covers in use at a specific time slot.
     */
    protected function getCoversAtTime($reservations, Carbon $slotTime, int $slotDuration, int $reservationDuration): int
    {
        $covers = 0;

        foreach ($reservations as $reservation) {
            $resStart = Carbon::parse($reservation->reservation_date->format('Y-m-d') . ' ' . $reservation->reservation_time);
            $resDuration = $reservation->duration_minutes ?? $reservationDuration;
            $resEnd = $resStart->copy()->addMinutes($resDuration);

            // Check if the reservation overlaps with this slot
            $slotEnd = $slotTime->copy()->addMinutes($slotDuration);

            if ($slotTime->lt($resEnd) && $slotEnd->gt($resStart)) {
                $covers += $reservation->party_size;
            }
        }

        return $covers;
    }
}
