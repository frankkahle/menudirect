<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Client\Traits\AuthorizesRestaurantSite;
use App\Http\Controllers\Client\Traits\ClearsRestaurantSiteCache;
use App\Models\RestaurantSite;
use App\Models\HolidayHour;
use App\Services\Images\ImageProcessor;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class RestaurantSiteController extends Controller
{
    use AuthorizesRestaurantSite;
    use ClearsRestaurantSiteCache;

    /**
     * Display a listing of the client's restaurant sites.
     */
    public function index()
    {
        $query = RestaurantSite::orderBy('created_at', 'desc');

        // Admins see all sites (replaces portal-era client impersonation workflow).
        // Owners see only their own.
        if (!auth()->user()->is_admin) {
            $query->where('client_id', auth()->id());
        }

        $sites = $query->get();

        return view('client.restaurant.index', compact('sites'));
    }

    /**
     * Show the form for creating a new restaurant site.
     */
    public function create()
    {
        return view('client.restaurant.create');
    }

    /**
     * Store a newly created restaurant site.
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'business_name' => ['required', 'string', 'max:255'],
            'slug' => ['required', 'string', 'max:100', 'alpha_dash', 'unique:menudirect.restaurant_sites,slug'],
            'tagline' => ['nullable', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'email' => ['nullable', 'email', 'max:255'],
            'address' => ['nullable', 'string', 'max:500'],
        ]);

        $data['client_id'] = auth()->id();
        $data['status'] = RestaurantSite::STATUS_DEMO;
        $data['plan'] = RestaurantSite::PLAN_SELFSERVICE;
        $data['colors'] = RestaurantSite::DEFAULT_COLORS;
        $data['hours'] = RestaurantSite::DEFAULT_HOURS;

        $site = RestaurantSite::create($data);

        return redirect()->route('client.restaurant.show', $site)
            ->with('status', 'Restaurant site created successfully!');
    }

    /**
     * Display the specified restaurant site dashboard.
     */
    public function show(RestaurantSite $site)
    {
        $this->authorizeSite($site);

        $site->load(['categories.items', 'announcements']);

        $stats = [
            'categories' => $site->categories()->count(),
            'items' => $site->categories()->withCount('items')->get()->sum('items_count'),
            'featured' => $site->featuredItems()->count(),
            'announcements' => $site->announcements()->where('active', true)->count(),
        ];

        return view('client.restaurant.show', compact('site', 'stats'));
    }

    /**
     * Show the form for editing the restaurant site.
     */
    public function edit(RestaurantSite $site)
    {
        $this->authorizeSite($site);

        return view('client.restaurant.edit', compact('site'));
    }

    /**
     * Update the specified restaurant site.
     */
    public function update(Request $request, RestaurantSite $site)
    {
        $this->authorizeSite($site);

        $data = $request->validate([
            'business_name' => ['required', 'string', 'max:255'],
            'slug' => ['required', 'string', 'max:100', 'alpha_dash', Rule::unique('menudirect.restaurant_sites')->ignore($site->id)],
            'tagline' => ['nullable', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'email' => ['nullable', 'email', 'max:255'],
            'address' => ['nullable', 'string', 'max:500'],
            'timezone' => ['nullable', 'string', 'max:64', function ($attr, $val, $fail) {
                if ($val && !in_array($val, timezone_identifiers_list())) {
                    $fail('Invalid timezone.');
                }
            }],
            'force_closed' => ['nullable', 'boolean'],
            'closure_message' => ['nullable', 'string', 'max:255'],
            'social_proof' => ['nullable', 'array'],
            'social_proof.rating' => ['nullable', 'numeric', 'min:0', 'max:5'],
            'social_proof.reviews' => ['nullable', 'string', 'max:50'],
            'social_proof.followers' => ['nullable', 'string', 'max:50'],
            'social_proof.recommendation' => ['nullable', 'string', 'max:100'],
            'settings' => ['nullable', 'array'],
            'settings.features' => ['nullable', 'array', 'max:12'],
            'settings.features.*' => ['nullable', 'string', 'max:60'],
            'settings.testimonials' => ['nullable', 'array'],
            'settings.cta_text' => ['nullable', 'string', 'max:50'],
            'settings.cta_url' => ['nullable', 'string', 'max:255'],
            'settings.secondary_cta_text' => ['nullable', 'string', 'max:50'],
            'settings.secondary_cta_url' => ['nullable', 'string', 'max:255'],
            'settings.social_links' => ['nullable', 'array'],
            'settings.social_links.facebook' => ['nullable', 'url', 'max:255'],
            'settings.social_links.instagram' => ['nullable', 'url', 'max:255'],
            'settings.social_links.tiktok' => ['nullable', 'url', 'max:255'],
            'settings.social_links.twitter' => ['nullable', 'url', 'max:255'],
            'settings.social_links.google' => ['nullable', 'url', 'max:255'],
            'settings.sister_sites_eyebrow' => ['nullable', 'string', 'max:60'],
            'settings.sister_sites_heading' => ['nullable', 'string', 'max:100'],
            'settings.sister_sites' => ['nullable', 'array'],
            'settings.sister_sites.*' => [
                'string',
                // SECURITY: the slug must belong to a site owned by the same client
                \Illuminate\Validation\Rule::exists('menudirect.restaurant_sites', 'slug')
                    ->where(fn ($q) => $q->where('client_id', $site->client_id)->where('id', '!=', $site->id)),
            ],
            'settings.template' => ['nullable', 'string', \Illuminate\Validation\Rule::in(array_keys(config('restaurant_templates', [])))],
        ]);

        // Merge settings to preserve fields not in the form (e.g., payment_note, gallery, domain_aliases)
        if (isset($data['settings'])) {
            // Checkbox groups that should be cleared when form submits with none checked
            $existingSettings = $site->settings ?? [];
            if ($request->has('settings') && !$request->has('settings.sister_sites')) {
                unset($existingSettings['sister_sites']);
            }

            $data['settings'] = array_merge($existingSettings, $data['settings']);

            // Remove empty social links
            if (isset($data['settings']['social_links'])) {
                $data['settings']['social_links'] = array_filter($data['settings']['social_links']);
            }
        }

        // Normalize force_closed to boolean (checkbox sends "1" or nothing)
        $data['force_closed'] = $request->boolean('force_closed');
        if (!$data['force_closed']) {
            $data['closure_message'] = null; // clear the message when re-opening
        }

        // Filter empty feature rows (user can delete by clearing the input)
        if (isset($data['settings']['features']) && is_array($data['settings']['features'])) {
            $data['settings']['features'] = array_values(array_filter(
                $data['settings']['features'],
                fn ($f) => is_string($f) && trim($f) !== ''
            ));
        }

        $site->update($data);

        return redirect()->route('client.restaurant.show', $site)
            ->with('status', 'Restaurant site updated successfully!');
    }

    /**
     * Update the restaurant hours.
     */
    public function updateHours(Request $request, RestaurantSite $site)
    {
        $this->authorizeSite($site);

        $data = $request->validate([
            'hours' => ['required', 'array'],
            'hours.*' => ['nullable', 'string', 'max:100'],
        ]);

        $site->update(['hours' => $data['hours']]);

        return back()->with('status', 'Hours updated successfully!');
    }

    /**
     * Update the restaurant colors.
     */
    public function updateColors(Request $request, RestaurantSite $site)
    {
        $this->authorizeSite($site);

        $data = $request->validate([
            'colors' => ['required', 'array'],
            'colors.primary' => ['required', 'string', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'colors.secondary' => ['required', 'string', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'colors.accent' => ['required', 'string', 'regex:/^#[0-9A-Fa-f]{6}$/'],
        ]);

        $site->update(['colors' => $data['colors']]);

        return back()->with('status', 'Colors updated successfully!');
    }

    /**
     * Upload and update the restaurant logo.
     */
    public function uploadLogo(Request $request, RestaurantSite $site)
    {
        $this->authorizeSite($site);

        $request->validate([
            'logo' => ['required', 'image', 'mimes:jpeg,png,jpg,gif,webp', 'max:20480'],
        ]);

        if ($site->logo_path) {
            Storage::disk('public')->delete($site->logo_path);
        }

        $path = ImageProcessor::storeProcessed(
            $request->file('logo'),
            $site->getStoragePath(),
            filename: 'logo-' . time() . '.jpg',
        );
        $site->update(['logo_path' => $path]);

        return back()->with('status', 'Logo uploaded successfully!');
    }

    /**
     * Upload and update the restaurant cover photo.
     */
    public function uploadCoverPhoto(Request $request, RestaurantSite $site)
    {
        $this->authorizeSite($site);

        $request->validate([
            'cover_photo' => ['required', 'image', 'mimes:jpeg,png,jpg,gif,webp', 'max:20480'],
        ]);

        if ($site->cover_photo_path) {
            Storage::disk('public')->delete($site->cover_photo_path);
        }

        $path = ImageProcessor::storeProcessed(
            $request->file('cover_photo'),
            $site->getStoragePath(),
            filename: 'cover-' . time() . '.jpg',
        );
        $site->update(['cover_photo_path' => $path]);

        return back()->with('status', 'Cover photo uploaded successfully!');
    }

    /**
     * Remove the restaurant's logo.
     */
    public function removeLogo(RestaurantSite $site)
    {
        $this->authorizeSite($site);

        if ($site->logo_path) {
            Storage::disk('public')->delete($site->logo_path);
        }
        $site->update(['logo_path' => null]);

        return back()->with('status', 'Logo removed.');
    }

    /**
     * Remove the restaurant's cover photo.
     */
    public function removeCoverPhoto(RestaurantSite $site)
    {
        $this->authorizeSite($site);

        if ($site->cover_photo_path) {
            Storage::disk('public')->delete($site->cover_photo_path);
        }
        $site->update(['cover_photo_path' => null]);

        return back()->with('status', 'Cover photo removed.');
    }

    /**
     * Preview the restaurant site.
     */
    public function preview(RestaurantSite $site)
    {
        $this->authorizeSite($site);

        // Return preview data for iframe or new tab
        return response()->json([
            'url' => $site->getPublicUrl(),
            'data' => $site->toSiteArray(),
        ]);
    }

    /**
     * Publish the restaurant site.
     */
    public function publish(Request $request, RestaurantSite $site)
    {
        $this->authorizeSite($site);

        if (!$site->isPublished()) {
            $site->update(['published_at' => now()]);
        }

        // Change status to active if it's still demo
        if ($site->status === RestaurantSite::STATUS_DEMO) {
            $site->update(['status' => RestaurantSite::STATUS_ACTIVE]);
        }

        return back()->with('status', 'Your restaurant site is now live!');
    }

    /**
     * Resolve the storage path for a gallery entry that may be either a string
     * (legacy format) or an associative array of ['path' => ..., 'caption' => ...].
     */
    private function galleryEntryPath($entry): ?string
    {
        if (is_string($entry)) return $entry;
        if (is_array($entry)) return $entry['path'] ?? null;
        return null;
    }

    /**
     * Upload a photo to the gallery.
     */
    public function uploadGalleryPhoto(Request $request, RestaurantSite $site)
    {
        $this->authorizeSite($site);

        $request->validate([
            'photo' => ['required', 'image', 'mimes:jpeg,png,jpg,gif,webp', 'max:20480'],
        ]);

        // Get current gallery
        $settings = $site->settings ?? [];
        $gallery = $settings['gallery'] ?? [];

        // Check max gallery size
        if (count($gallery) >= 10) {
            return back()->withErrors(['photo' => 'Maximum 10 photos allowed in gallery.']);
        }

        $path = ImageProcessor::storeProcessed(
            $request->file('photo'),
            $site->getStoragePath() . '/gallery',
            filename: 'gallery-' . time() . '-' . Str::random(6) . '.jpg',
        );
        $gallery[] = ['path' => $path, 'caption' => null];

        // Update settings
        $settings['gallery'] = $gallery;
        $site->update(['settings' => $settings]);

        return back()->with('status', 'Photo added to gallery!');
    }

    /**
     * Replace an existing gallery photo at the given index with a newly uploaded one.
     */
    public function replaceGalleryPhoto(Request $request, RestaurantSite $site, int $index)
    {
        $this->authorizeSite($site);

        $request->validate([
            'image' => ['required', 'image', 'mimes:jpeg,png,jpg,gif,webp', 'max:20480'],
        ]);

        $settings = $site->settings ?? [];
        $gallery = $settings['gallery'] ?? [];

        if (!isset($gallery[$index])) {
            return back()->withErrors(['gallery' => 'Photo not found.']);
        }

        $oldPath = $this->galleryEntryPath($gallery[$index]);
        if ($oldPath) {
            Storage::disk('public')->delete($oldPath);
        }

        $path = ImageProcessor::storeProcessed(
            $request->file('image'),
            $site->getStoragePath() . '/gallery',
            filename: 'gallery-' . time() . '-' . Str::random(6) . '.jpg',
        );

        // Preserve any existing caption when re-cropping/replacing the image
        $existingCaption = is_array($gallery[$index]) ? ($gallery[$index]['caption'] ?? null) : null;
        $gallery[$index] = ['path' => $path, 'caption' => $existingCaption];

        $settings['gallery'] = $gallery;
        $site->update(['settings' => $settings]);

        return back()->with('status', 'Photo replaced.');
    }

    /**
     * Update the caption text for a gallery photo at the given index.
     */
    public function updateGalleryCaption(Request $request, RestaurantSite $site, int $index)
    {
        $this->authorizeSite($site);

        $data = $request->validate([
            'caption' => ['nullable', 'string', 'max:200'],
        ]);

        $settings = $site->settings ?? [];
        $gallery = $settings['gallery'] ?? [];

        if (!isset($gallery[$index])) {
            return response()->json(['success' => false, 'message' => 'Photo not found.'], 404);
        }

        $path = $this->galleryEntryPath($gallery[$index]);
        $caption = trim((string) ($data['caption'] ?? '')) ?: null;
        $gallery[$index] = ['path' => $path, 'caption' => $caption];

        $settings['gallery'] = $gallery;
        $site->update(['settings' => $settings]);

        if ($request->wantsJson()) {
            return response()->json(['success' => true, 'caption' => $caption]);
        }
        return back()->with('status', 'Caption saved.');
    }

    /**
     * Delete a photo from the gallery.
     */
    public function deleteGalleryPhoto(Request $request, RestaurantSite $site, int $index)
    {
        $this->authorizeSite($site);

        $settings = $site->settings ?? [];
        $gallery = $settings['gallery'] ?? [];

        if (!isset($gallery[$index])) {
            return back()->withErrors(['gallery' => 'Photo not found.']);
        }

        $path = $this->galleryEntryPath($gallery[$index]);
        if ($path) {
            Storage::disk('public')->delete($path);
        }

        // Remove from array and reindex
        array_splice($gallery, $index, 1);

        // Update settings
        $settings['gallery'] = $gallery;
        $site->update(['settings' => $settings]);

        return back()->with('status', 'Photo removed from gallery.');
    }

    /**
     * Store a new holiday hour exception.
     */
    public function storeHolidayHour(Request $request, RestaurantSite $site)
    {
        $this->authorizeSite($site);

        $data = $request->validate([
            'date' => ['required', 'date', 'after_or_equal:today'],
            'hours' => ['required', 'string', 'max:100'],
            'label' => ['nullable', 'string', 'max:100'],
        ]);

        // Check for duplicate date
        $exists = $site->holidayHours()->whereDate('date', $data['date'])->exists();
        if ($exists) {
            return back()->withErrors(['date' => 'A holiday hour entry already exists for this date.']);
        }

        $site->holidayHours()->create($data);

        return back()->with('status', 'Holiday hours added!');
    }

    /**
     * Update a holiday hour exception.
     */
    public function updateHolidayHour(Request $request, RestaurantSite $site, HolidayHour $holidayHour)
    {
        $this->authorizeSite($site);

        // Verify the holiday hour belongs to this site
        if ($holidayHour->restaurant_site_id !== $site->id) {
            abort(403);
        }

        $data = $request->validate([
            'hours' => ['required', 'string', 'max:100'],
            'label' => ['nullable', 'string', 'max:100'],
        ]);

        $holidayHour->update($data);

        return back()->with('status', 'Holiday hours updated!');
    }

    /**
     * Delete a holiday hour exception.
     */
    public function destroyHolidayHour(RestaurantSite $site, HolidayHour $holidayHour)
    {
        $this->authorizeSite($site);

        // Verify the holiday hour belongs to this site
        if ($holidayHour->restaurant_site_id !== $site->id) {
            abort(403);
        }

        $holidayHour->delete();

        return back()->with('status', 'Holiday hours removed.');
    }

    /**
     * Update ordering settings.
     */
    public function updateOrderingSettings(Request $request, RestaurantSite $site)
    {
        $this->authorizeSite($site);

        $data = $request->validate([
            'ordering_enabled' => ['boolean'],
            'latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],
            'ordering_settings' => ['nullable', 'array'],
            'ordering_settings.accepts_delivery' => ['boolean'],
            'ordering_settings.accepts_pickup' => ['boolean'],
            'ordering_settings.minimum_order' => ['nullable', 'numeric', 'min:0'],
            'ordering_settings.delivery_fee' => ['nullable', 'numeric', 'min:0'],
            'ordering_settings.delivery_radius_km' => ['nullable', 'numeric', 'min:0'],
            'ordering_settings.estimated_prep_time_minutes' => ['nullable', 'integer', 'min:5', 'max:180'],
            'ordering_settings.notification_email' => ['nullable', 'email', 'max:255'],
            'ordering_settings.notification_phone' => ['nullable', 'string', 'max:50'],
            'ordering_settings.auto_confirm' => ['boolean'],
            'ordering_settings.tax_rate' => ['nullable', 'numeric', 'min:0', 'max:1'],
        ]);

        $updateData = [
            'ordering_enabled' => $data['ordering_enabled'] ?? false,
            'ordering_settings' => $data['ordering_settings'] ?? [],
        ];

        // Save coordinates if provided
        if (array_key_exists('latitude', $data)) {
            $updateData['latitude'] = $data['latitude'];
        }
        if (array_key_exists('longitude', $data)) {
            $updateData['longitude'] = $data['longitude'];
        }

        $site->update($updateData);

        return back()->with('status', 'Ordering settings updated!');
    }

    /**
     * Update reservation settings.
     */
    public function updateReservationSettings(Request $request, RestaurantSite $site)
    {
        $this->authorizeSite($site);

        $data = $request->validate([
            'reservation_settings' => ['nullable', 'array'],
            'reservation_settings.enabled' => ['boolean'],
            'reservation_settings.type' => ['nullable', 'string', 'in:email,opentable,resy,custom,built_in'],
            'reservation_settings.email' => ['nullable', 'email', 'max:255'],
            'reservation_settings.opentable_id' => ['nullable', 'string', 'max:100'],
            'reservation_settings.resy_venue_id' => ['nullable', 'string', 'max:100'],
            'reservation_settings.custom_embed' => ['nullable', 'string', 'max:5000'],
            // Built-in reservation settings
            'reservation_settings.max_covers_per_slot' => ['nullable', 'integer', 'min:1', 'max:500'],
            'reservation_settings.slot_duration_minutes' => ['nullable', 'integer', 'in:15,30,60'],
            'reservation_settings.max_party_size' => ['nullable', 'integer', 'min:1', 'max:50'],
            'reservation_settings.default_duration_minutes' => ['nullable', 'integer', 'min:30', 'max:300'],
            'reservation_settings.advance_booking_days' => ['nullable', 'integer', 'min:1', 'max:90'],
            'reservation_settings.min_advance_hours' => ['nullable', 'integer', 'min:0', 'max:72'],
            'reservation_settings.auto_confirm' => ['boolean'],
            'reservation_settings.notification_email' => ['nullable', 'email', 'max:255'],
            'reservation_settings.notification_phone' => ['nullable', 'string', 'max:50'],
            'reservation_settings.confirmation_message' => ['nullable', 'string', 'max:1000'],
            'reservation_settings.cancellation_policy' => ['nullable', 'string', 'max:1000'],
            'reservation_settings.blocked_dates' => ['nullable', 'array'],
            'reservation_settings.blocked_dates.*' => ['date'],
        ]);

        $reservationSettings = $data['reservation_settings'] ?? [];

        // Ensure enabled is a boolean
        $reservationSettings['enabled'] = (bool) ($reservationSettings['enabled'] ?? false);

        $site->update([
            'reservation_settings' => $reservationSettings,
        ]);

        return back()->with('status', 'Reservation settings updated!');
    }

    /**
     * Update catering settings.
     */
    public function updateCateringSettings(Request $request, RestaurantSite $site)
    {
        $this->authorizeSite($site);

        $data = $request->validate([
            'catering_enabled' => ['boolean'],
            'catering_settings' => ['nullable', 'array'],
            'catering_settings.notification_email' => ['nullable', 'email', 'max:255'],
            'catering_settings.notification_phone' => ['nullable', 'string', 'max:50'],
            'catering_settings.lead_time_hours' => ['nullable', 'integer', 'min:1', 'max:720'],
            'catering_settings.min_guests' => ['nullable', 'integer', 'min:1', 'max:1000'],
            'catering_settings.deposit_required' => ['boolean'],
            'catering_settings.custom_message' => ['nullable', 'string', 'max:1000'],
        ]);

        $site->update([
            'catering_enabled' => $request->boolean('catering_enabled'),
            'catering_settings' => $data['catering_settings'] ?? [],
        ]);

        return back()->with('status', 'Catering settings updated!');
    }

    /**
     * Show the template picker.
     */
    public function templates(RestaurantSite $site)
    {
        $this->authorizeSite($site);

        $templates = config('restaurant_templates', []);
        $currentTemplate = $site->settings['template'] ?? 'restaurant';

        return view('client.restaurant.templates', compact('site', 'templates', 'currentTemplate'));
    }

    /**
     * Update the site's template.
     */
    public function updateTemplate(Request $request, RestaurantSite $site)
    {
        $this->authorizeSite($site);

        $data = $request->validate([
            'template' => ['required', 'string', Rule::in(array_keys(config('restaurant_templates', [])))],
        ]);

        $settings = $site->settings ?? [];
        $settings['template'] = $data['template'];
        $site->settings = $settings;
        $site->save();

        // Clear sos-tech cache so the change takes effect immediately
        try {
            $secret = config('services.sostech.cache_clear_secret');
            if ($secret) {
                \Illuminate\Support\Facades\Http::post(config('services.sostech.url') . '/api/cache-clear', [
                    'secret' => $secret,
                ]);
            }
        } catch (\Exception $e) {
            // Non-critical — cache will expire on its own
        }

        $allTemplates = config('restaurant_templates', []);
        $templateName = $allTemplates[$data['template']]['name'] ?? $data['template'];
        return back()->with('status', "Template changed to \"{$templateName}\"!");
    }

    /**
     * Show locations management page.
     */
    public function locations(RestaurantSite $site)
    {
        $this->authorizeSite($site);

        // Get all available sites that can be linked (same client, not already linked to another parent)
        $availableSites = RestaurantSite::where('client_id', auth()->id())
            ->where('id', '!=', $site->id)
            ->whereNull('parent_site_id')
            ->whereDoesntHave('childSites')
            ->get();

        $childSites = $site->childSites()->get();
        $parentSite = $site->parentSite;

        return view('client.restaurant.locations', compact('site', 'availableSites', 'childSites', 'parentSite'));
    }

    /**
     * Link a site as a child location.
     */
    public function linkLocation(Request $request, RestaurantSite $site)
    {
        $this->authorizeSite($site);

        $data = $request->validate([
            'child_site_id' => ['required', 'integer', 'exists:menudirect.restaurant_sites,id'],
        ]);

        $childSite = RestaurantSite::findOrFail($data['child_site_id']);

        // Verify the child site belongs to the same client
        if ($childSite->client_id !== auth()->id()) {
            abort(403, 'You do not own this site.');
        }

        try {
            $childSite->linkToParent($site);
            return back()->with('status', "{$childSite->business_name} has been linked as a location.");
        } catch (\InvalidArgumentException $e) {
            return back()->withErrors(['child_site_id' => $e->getMessage()]);
        }
    }

    /**
     * Unlink a child location.
     */
    public function unlinkLocation(RestaurantSite $site, RestaurantSite $childSite)
    {
        $this->authorizeSite($site);

        // Verify the child site belongs to this parent
        if ($childSite->parent_site_id !== $site->id) {
            abort(403, 'This site is not a child of the parent site.');
        }

        $childSite->unlinkFromParent();

        return back()->with('status', "{$childSite->business_name} has been unlinked.");
    }

    /**
     * Set primary location.
     */
    public function setPrimaryLocation(RestaurantSite $site, RestaurantSite $location)
    {
        $this->authorizeSite($site);

        // Verify they're in the same location group
        if ($location->parent_site_id !== $site->id && $location->id !== $site->id) {
            abort(403, 'Invalid location.');
        }

        // Clear all primary flags in the group
        RestaurantSite::where('parent_site_id', $site->id)
            ->orWhere('id', $site->id)
            ->update(['is_primary_location' => false]);

        // Set the new primary
        $location->update(['is_primary_location' => true]);

        return back()->with('status', "{$location->business_name} is now the primary location.");
    }

}
