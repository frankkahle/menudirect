<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Client\Traits\AuthorizesRestaurantSite;
use App\Models\Announcement;
use App\Models\RestaurantSite;
use App\Services\Images\ImageProcessor;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class RestaurantAnnouncementController extends Controller
{
    use AuthorizesRestaurantSite;

    /**
     * Display the announcements manager.
     */
    public function index(RestaurantSite $site)
    {
        $this->authorizeSite($site);

        $announcements = $site->announcements()
            ->orderBy('created_at', 'desc')
            ->get();

        return view('client.restaurant.announcements', compact('site', 'announcements'));
    }

    /**
     * Store a new announcement.
     */
    public function store(Request $request, RestaurantSite $site)
    {
        $this->authorizeSite($site);

        $data = $this->validateAnnouncement($request);

        $data['restaurant_site_id'] = $site->id;
        $data['active'] = $data['active'] ?? true;

        if ($request->hasFile('image')) {
            $data['image_path'] = ImageProcessor::storeProcessed(
                $request->file('image'),
                $site->getStoragePath() . '/announcements',
                filename: 'ann-' . time() . '-' . Str::random(6) . '.jpg',
            );
        }

        unset($data['image']);

        $announcement = Announcement::create($data);

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'announcement' => $announcement,
            ]);
        }

        return back()->with('status', 'Announcement created successfully!');
    }

    /**
     * Update an announcement.
     */
    public function update(Request $request, RestaurantSite $site, Announcement $announcement)
    {
        $this->authorizeSite($site);
        $this->authorizeAnnouncement($site, $announcement);

        $data = $this->validateAnnouncement($request, $announcement);

        if ($request->hasFile('image')) {
            if ($announcement->image_path) {
                Storage::disk('public')->delete($announcement->image_path);
            }
            $data['image_path'] = ImageProcessor::storeProcessed(
                $request->file('image'),
                $site->getStoragePath() . '/announcements',
                filename: 'ann-' . time() . '-' . Str::random(6) . '.jpg',
            );
        } elseif ($request->boolean('remove_image') && $announcement->image_path) {
            Storage::disk('public')->delete($announcement->image_path);
            $data['image_path'] = null;
        }

        unset($data['image']);

        $announcement->update($data);

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'announcement' => $announcement->fresh(),
            ]);
        }

        return back()->with('status', 'Announcement updated successfully!');
    }

    /**
     * Delete an announcement.
     */
    public function destroy(Request $request, RestaurantSite $site, Announcement $announcement)
    {
        $this->authorizeSite($site);
        $this->authorizeAnnouncement($site, $announcement);

        $announcement->delete();

        if ($request->wantsJson()) {
            return response()->json(['success' => true]);
        }

        return back()->with('status', 'Announcement deleted successfully!');
    }

    /**
     * Replace only the image on an existing announcement (used by re-crop flow).
     */
    public function updateImage(Request $request, RestaurantSite $site, Announcement $announcement)
    {
        $this->authorizeSite($site);
        $this->authorizeAnnouncement($site, $announcement);

        $request->validate([
            'image' => ['required', 'image', 'mimes:jpeg,png,jpg,gif,webp', 'max:20480'],
        ]);

        if ($announcement->image_path) {
            Storage::disk('public')->delete($announcement->image_path);
        }

        $path = ImageProcessor::storeProcessed(
            $request->file('image'),
            $site->getStoragePath() . '/announcements',
            filename: 'ann-' . time() . '-' . Str::random(6) . '.jpg',
        );

        $announcement->update(['image_path' => $path]);

        if ($request->wantsJson()) {
            return response()->json(['success' => true, 'image_url' => $announcement->fresh()->image_url]);
        }

        return back()->with('status', 'Announcement image replaced.');
    }

    /**
     * Toggle announcement active status.
     */
    public function toggle(Request $request, RestaurantSite $site, Announcement $announcement)
    {
        $this->authorizeSite($site);
        $this->authorizeAnnouncement($site, $announcement);

        $newStatus = $announcement->toggleActive();

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'active' => $newStatus,
            ]);
        }

        return back()->with('status', $newStatus ? 'Announcement activated!' : 'Announcement deactivated.');
    }

    /**
     * Verify the announcement belongs to this site.
     */
    protected function authorizeAnnouncement(RestaurantSite $site, Announcement $announcement): void
    {
        if ($announcement->restaurant_site_id !== $site->id) {
            abort(403, 'This announcement does not belong to this restaurant site.');
        }
    }

    protected function validateAnnouncement(Request $request, ?Announcement $existing = null): array
    {
        $data = $request->validate([
            'message' => ['nullable', 'string', 'max:500'],
            'title' => ['nullable', 'string', 'max:120'],
            'image' => ['nullable', 'image', 'mimes:jpeg,png,jpg,gif,webp', 'max:20480'],
            'link_url' => ['nullable', 'url', 'max:500'],
            'type' => ['required', 'string', 'in:special,closure,alert,info'],
            'starts_at' => ['nullable', 'date'],
            'ends_at' => ['nullable', 'date', 'after_or_equal:starts_at'],
            'active' => ['nullable', 'boolean'],
            'remove_image' => ['nullable', 'boolean'],
        ]);

        $hasMessage = !empty($data['message'] ?? null);
        $hasTitle = !empty($data['title'] ?? null);
        $hasNewImage = $request->hasFile('image');
        $willKeepImage = $existing && $existing->image_path && !$request->boolean('remove_image') && !$hasNewImage;
        $hasImage = $hasNewImage || $willKeepImage;

        if (!$hasMessage && !$hasTitle && !$hasImage) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'message' => 'Provide at least a message, title, or image.',
            ]);
        }

        return $data;
    }
}
