<?php

namespace App\Services;

use App\Models\DeliveryZone;
use App\Models\RestaurantSite;

class DeliveryZoneService
{
    /**
     * Calculate the distance between two lat/lng points using the Haversine formula.
     *
     * @return float Distance in kilometers
     */
    public function calculateDistance(float $lat1, float $lng1, float $lat2, float $lng2): float
    {
        $earthRadius = 6371; // km

        $dLat = deg2rad($lat2 - $lat1);
        $dLng = deg2rad($lng2 - $lng1);

        $a = sin($dLat / 2) * sin($dLat / 2)
           + cos(deg2rad($lat1)) * cos(deg2rad($lat2))
           * sin($dLng / 2) * sin($dLng / 2);

        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        return $earthRadius * $c;
    }

    /**
     * Find the matching delivery zone for a given distance.
     * Returns the first zone whose radius_km >= the distance.
     */
    public function findZoneForDistance(RestaurantSite $site, float $distanceKm): ?DeliveryZone
    {
        return $site->deliveryZones()
            ->active()
            ->ordered()
            ->where('radius_km', '>=', $distanceKm)
            ->first();
    }

    /**
     * Validate a delivery address against the restaurant's delivery zones.
     *
     * @return array|null Zone details if in range, null if out of range
     */
    public function validateDeliveryAddress(RestaurantSite $site, float $lat, float $lng): ?array
    {
        if (!$site->latitude || !$site->longitude) {
            return null;
        }

        $distanceKm = $this->calculateDistance(
            (float) $site->latitude,
            (float) $site->longitude,
            $lat,
            $lng
        );

        $zone = $this->findZoneForDistance($site, $distanceKm);

        if (!$zone) {
            return null;
        }

        return [
            'zone_name' => $zone->name,
            'distance_km' => round($distanceKm, 2),
            'delivery_fee' => (float) $zone->delivery_fee,
            'minimum_order' => (float) $zone->minimum_order,
            'estimated_delivery_minutes' => $zone->estimated_delivery_minutes,
        ];
    }

    /**
     * Get the delivery fee for a given address.
     * Uses zone-based fee if zones are configured, falls back to flat fee.
     */
    public function getDeliveryFee(RestaurantSite $site, ?float $lat = null, ?float $lng = null): float
    {
        // If we have zones and coordinates, use zone-based pricing
        if ($site->canUseDeliveryZones() && $lat && $lng && $site->latitude && $site->longitude) {
            $zones = $site->deliveryZones()->active()->count();
            if ($zones > 0) {
                $result = $this->validateDeliveryAddress($site, $lat, $lng);
                if ($result) {
                    return $result['delivery_fee'];
                }
                // Out of range — return 0 (should be rejected before this point)
                return 0;
            }
        }

        // Flat fee fallback
        $settings = $site->getOrderingSettings();
        return (float) ($settings['delivery_fee'] ?? 0);
    }
}
