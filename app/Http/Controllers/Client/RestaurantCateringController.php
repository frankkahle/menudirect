<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Models\CateringInquiry;
use App\Models\CateringPackage;
use App\Models\RestaurantSite;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class RestaurantCateringController extends Controller
{
    /**
     * List catering inquiries.
     */
    public function index(Request $request, RestaurantSite $site)
    {
        $this->authorizeSite($site);

        $query = $site->cateringInquiries()->with('cateringPackage')->orderBy('created_at', 'desc');

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        $inquiries = $query->paginate(25);

        $newCount = $site->cateringInquiries()->new()->count();
        $activeCount = $site->cateringInquiries()->active()->count();
        $bookedCount = $site->cateringInquiries()->booked()->count();

        return view('client.restaurant.catering.index', compact(
            'site', 'inquiries', 'newCount', 'activeCount', 'bookedCount'
        ));
    }

    /**
     * Show inquiry details.
     */
    public function show(RestaurantSite $site, CateringInquiry $inquiry)
    {
        $this->authorizeSite($site);
        $this->authorizeInquiry($site, $inquiry);

        $inquiry->load('cateringPackage');

        return view('client.restaurant.catering.show', compact('site', 'inquiry'));
    }

    /**
     * Update inquiry status.
     */
    public function updateStatus(Request $request, RestaurantSite $site, CateringInquiry $inquiry)
    {
        $this->authorizeSite($site);
        $this->authorizeInquiry($site, $inquiry);

        $status = $request->input('status');

        $result = match ($status) {
            'contacted' => $inquiry->markContacted(),
            'quoted' => $inquiry->markQuoted(),
            'booked' => $inquiry->markBooked(),
            'declined' => $inquiry->markDeclined(),
            'cancelled' => $inquiry->cancel(),
            default => false,
        };

        if (!$result) {
            return back()->with('error', 'Cannot change to that status.');
        }

        return back()->with('status', 'Inquiry status updated to ' . $inquiry->status_label . '.');
    }

    /**
     * Add admin notes to inquiry.
     */
    public function addNote(Request $request, RestaurantSite $site, CateringInquiry $inquiry)
    {
        $this->authorizeSite($site);
        $this->authorizeInquiry($site, $inquiry);

        $validated = $request->validate([
            'admin_notes' => 'required|string|max:5000',
        ]);

        $inquiry->update(['admin_notes' => $validated['admin_notes']]);

        return back()->with('status', 'Notes updated.');
    }

    /**
     * Manage catering packages.
     */
    public function packages(RestaurantSite $site)
    {
        $this->authorizeSite($site);

        $packages = $site->cateringPackages()->ordered()->get();

        return view('client.restaurant.catering.packages', compact('site', 'packages'));
    }

    /**
     * Store a new catering package.
     */
    public function storePackage(Request $request, RestaurantSite $site)
    {
        $this->authorizeSite($site);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:2000',
            'price' => 'required|numeric|min:0',
            'price_type' => 'required|in:per_person,flat',
            'min_guests' => 'nullable|integer|min:1',
            'max_guests' => 'nullable|integer|min:1',
            'lead_time_hours' => 'nullable|integer|min:1',
            'includes' => 'nullable|array',
            'includes.*' => 'string|max:255',
        ]);

        $maxOrder = $site->cateringPackages()->max('sort_order') ?? 0;
        $validated['sort_order'] = $maxOrder + 1;

        $site->cateringPackages()->create($validated);

        return back()->with('status', 'Catering package created.');
    }

    /**
     * Update a catering package.
     */
    public function updatePackage(Request $request, RestaurantSite $site, CateringPackage $package)
    {
        $this->authorizeSite($site);
        $this->authorizePackage($site, $package);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:2000',
            'price' => 'required|numeric|min:0',
            'price_type' => 'required|in:per_person,flat',
            'min_guests' => 'nullable|integer|min:1',
            'max_guests' => 'nullable|integer|min:1',
            'lead_time_hours' => 'nullable|integer|min:1',
            'includes' => 'nullable|array',
            'includes.*' => 'string|max:255',
            'active' => 'nullable|boolean',
        ]);

        $validated['active'] = $request->boolean('active', true);

        $package->update($validated);

        return back()->with('status', 'Package updated.');
    }

    /**
     * Delete a catering package.
     */
    public function destroyPackage(RestaurantSite $site, CateringPackage $package)
    {
        $this->authorizeSite($site);
        $this->authorizePackage($site, $package);

        if ($package->image_path) {
            Storage::disk('public')->delete($package->image_path);
        }

        $package->delete();

        return back()->with('status', 'Package deleted.');
    }

    /**
     * Reorder catering packages.
     */
    public function reorderPackages(Request $request, RestaurantSite $site)
    {
        $this->authorizeSite($site);

        $validated = $request->validate([
            'order' => 'required|array',
            'order.*' => 'integer|exists:menudirect.catering_packages,id',
        ]);

        foreach ($validated['order'] as $index => $id) {
            $site->cateringPackages()->where('id', $id)->update(['sort_order' => $index]);
        }

        return response()->json(['success' => true]);
    }

    /**
     * Upload package image.
     */
    public function uploadPackageImage(Request $request, RestaurantSite $site, CateringPackage $package)
    {
        $this->authorizeSite($site);
        $this->authorizePackage($site, $package);

        $request->validate([
            'image' => 'required|image|max:20480',
        ]);

        // Delete old image
        if ($package->image_path) {
            Storage::disk('public')->delete($package->image_path);
        }

        $path = $request->file('image')->store(
            $site->getStoragePath() . '/catering',
            'public'
        );

        $package->update(['image_path' => $path]);

        return back()->with('status', 'Package image uploaded.');
    }

    protected function authorizeSite(RestaurantSite $site): void
    {
        if ($site->client_id !== auth()->id()) {
            abort(403);
        }
    }

    protected function authorizeInquiry(RestaurantSite $site, CateringInquiry $inquiry): void
    {
        if ($inquiry->restaurant_site_id !== $site->id) {
            abort(404);
        }
    }

    protected function authorizePackage(RestaurantSite $site, CateringPackage $package): void
    {
        if ($package->restaurant_site_id !== $site->id) {
            abort(404);
        }
    }
}
