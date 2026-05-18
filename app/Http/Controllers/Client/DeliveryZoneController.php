<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Client\Traits\AuthorizesRestaurantSite;
use App\Models\DeliveryZone;
use App\Models\RestaurantSite;
use Illuminate\Http\Request;

class DeliveryZoneController extends Controller
{
    use AuthorizesRestaurantSite;

    /**
     * Store a new delivery zone.
     */
    public function store(Request $request, RestaurantSite $site)
    {
        $this->authorizeSite($site);

        if (!$site->canUseDeliveryZones()) {
            abort(403, 'Delivery zones require the MenuDirect Max plan.');
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'radius_km' => 'required|numeric|min:0.1|max:100',
            'delivery_fee' => 'required|numeric|min:0',
            'minimum_order' => 'nullable|numeric|min:0',
            'estimated_delivery_minutes' => 'required|integer|min:5|max:180',
            'is_active' => 'boolean',
        ]);

        $validated['minimum_order'] = $validated['minimum_order'] ?? 0;
        $validated['sort_order'] = $site->deliveryZones()->count();

        $site->deliveryZones()->create($validated);

        return back()->with('status', 'Delivery zone added successfully.');
    }

    /**
     * Update a delivery zone.
     */
    public function update(Request $request, RestaurantSite $site, DeliveryZone $zone)
    {
        $this->authorizeSite($site);
        $this->authorizeZone($site, $zone);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'radius_km' => 'required|numeric|min:0.1|max:100',
            'delivery_fee' => 'required|numeric|min:0',
            'minimum_order' => 'nullable|numeric|min:0',
            'estimated_delivery_minutes' => 'required|integer|min:5|max:180',
            'is_active' => 'boolean',
        ]);

        $validated['minimum_order'] = $validated['minimum_order'] ?? 0;

        $zone->update($validated);

        return back()->with('status', 'Delivery zone updated successfully.');
    }

    /**
     * Delete a delivery zone.
     */
    public function destroy(RestaurantSite $site, DeliveryZone $zone)
    {
        $this->authorizeSite($site);
        $this->authorizeZone($site, $zone);

        $zone->delete();

        return back()->with('status', 'Delivery zone deleted.');
    }

    /**
     * Verify the zone belongs to the site.
     */
    protected function authorizeZone(RestaurantSite $site, DeliveryZone $zone): void
    {
        if ($zone->restaurant_site_id !== $site->id) {
            abort(404);
        }
    }
}
