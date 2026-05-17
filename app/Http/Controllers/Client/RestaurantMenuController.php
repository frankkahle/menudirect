<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Client\Traits\AuthorizesRestaurantSite;
use App\Http\Controllers\Client\Traits\ClearsRestaurantSiteCache;
use App\Models\MenuCategory;
use App\Models\MenuItem;
use App\Models\RestaurantSite;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class RestaurantMenuController extends Controller
{
    use AuthorizesRestaurantSite;
    use ClearsRestaurantSiteCache;

    /**
     * Display the menu editor.
     */
    public function index(RestaurantSite $site)
    {
        $this->authorizeSite($site);

        $categories = $site->categories()
            ->with(['items' => fn($q) => $q->orderBy('sort_order')])
            ->orderBy('sort_order')
            ->get();

        return view('client.restaurant.menu', compact('site', 'categories'));
    }

    /**
     * Store a new menu category.
     */
    public function storeCategory(Request $request, RestaurantSite $site)
    {
        $this->authorizeSite($site);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:500'],
        ]);

        $data['restaurant_site_id'] = $site->id;
        $data['sort_order'] = ($site->categories()->max('sort_order') ?? 0) + 1;
        $data['active'] = true;

        $category = MenuCategory::create($data);
        $this->clearSiteCache($site);

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'category' => $category,
            ]);
        }

        return back()->with('status', 'Category created successfully!');
    }

    /**
     * Update a menu category.
     */
    public function updateCategory(Request $request, RestaurantSite $site, MenuCategory $category)
    {
        $this->authorizeSite($site);
        $this->authorizeCategory($site, $category);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:500'],
            'active' => ['nullable', 'boolean'],
        ]);

        $category->update($data);
        $this->clearSiteCache($site);

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'category' => $category->fresh(),
            ]);
        }

        return back()->with('status', 'Category updated successfully!');
    }

    /**
     * Delete a menu category.
     */
    public function destroyCategory(Request $request, RestaurantSite $site, MenuCategory $category)
    {
        $this->authorizeSite($site);
        $this->authorizeCategory($site, $category);

        // Delete all item images in this category
        foreach ($category->items as $item) {
            if ($item->image_path) {
                Storage::disk('public')->delete($item->image_path);
            }
        }

        $category->delete();
        $this->clearSiteCache($site);

        if ($request->wantsJson()) {
            return response()->json(['success' => true]);
        }

        return back()->with('status', 'Category deleted successfully!');
    }

    /**
     * Reorder menu categories.
     */
    public function reorderCategories(Request $request, RestaurantSite $site)
    {
        $this->authorizeSite($site);

        $request->validate([
            'categories' => ['required', 'array'],
            'categories.*' => ['required', 'integer', 'exists:menudirect.menu_categories,id'],
        ]);

        foreach ($request->categories as $index => $categoryId) {
            MenuCategory::where('id', $categoryId)
                ->where('restaurant_site_id', $site->id)
                ->update(['sort_order' => $index]);
        }

        $this->clearSiteCache($site);

        return response()->json(['success' => true]);
    }

    /**
     * Store a new menu item.
     */
    public function storeItem(Request $request, RestaurantSite $site, MenuCategory $category)
    {
        $this->authorizeSite($site);
        $this->authorizeCategory($site, $category);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:500'],
            'price' => ['required', 'numeric', 'min:0', 'max:9999.99'],
            'price_note' => ['nullable', 'string', 'max:100'],
            'featured' => ['nullable', 'boolean'],
            'dietary_info' => ['nullable', 'array'],
            'dietary_info.*' => ['string', 'in:vegetarian,vegan,gluten_free,dairy_free,nut_free,spicy,keto,low_carb,halal'],
            'badges' => ['nullable', 'array'],
            'badges.*' => ['string', 'in:popular,chefs_choice,new,seasonal,house_special'],
            'alt_text' => ['nullable', 'string', 'max:255'],
        ]);

        $data['menu_category_id'] = $category->id;
        $data['sort_order'] = $category->getNextItemSortOrder();
        $data['active'] = true;

        $item = MenuItem::create($data);
        $this->clearSiteCache($site);

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'item' => $item,
            ]);
        }

        return back()->with('status', 'Menu item created successfully!');
    }

    /**
     * Update a menu item.
     */
    public function updateItem(Request $request, RestaurantSite $site, MenuItem $item)
    {
        $this->authorizeSite($site);
        $this->authorizeItem($site, $item);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:500'],
            'price' => ['required', 'numeric', 'min:0', 'max:9999.99'],
            'price_note' => ['nullable', 'string', 'max:100'],
            'featured' => ['nullable', 'boolean'],
            'active' => ['nullable', 'boolean'],
            'dietary_info' => ['nullable', 'array'],
            'dietary_info.*' => ['string', 'in:vegetarian,vegan,gluten_free,dairy_free,nut_free,spicy,keto,low_carb,halal'],
            'badges' => ['nullable', 'array'],
            'badges.*' => ['string', 'in:popular,chefs_choice,new,seasonal,house_special'],
            'alt_text' => ['nullable', 'string', 'max:255'],
        ]);

        $item->update($data);
        $this->clearSiteCache($site);

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'item' => $item->fresh(),
            ]);
        }

        return back()->with('status', 'Menu item updated successfully!');
    }

    /**
     * Delete a menu item.
     */
    public function destroyItem(Request $request, RestaurantSite $site, MenuItem $item)
    {
        $this->authorizeSite($site);
        $this->authorizeItem($site, $item);

        // Delete item image if exists
        if ($item->image_path) {
            Storage::disk('public')->delete($item->image_path);
        }

        $item->delete();
        $this->clearSiteCache($site);

        if ($request->wantsJson()) {
            return response()->json(['success' => true]);
        }

        return back()->with('status', 'Menu item deleted successfully!');
    }

    /**
     * Reorder menu items within a category.
     */
    public function reorderItems(Request $request, RestaurantSite $site, MenuCategory $category)
    {
        $this->authorizeSite($site);
        $this->authorizeCategory($site, $category);

        $request->validate([
            'items' => ['required', 'array'],
            'items.*' => ['required', 'integer', 'exists:menudirect.menu_items,id'],
        ]);

        foreach ($request->items as $index => $itemId) {
            MenuItem::where('id', $itemId)
                ->where('menu_category_id', $category->id)
                ->update(['sort_order' => $index]);
        }

        $this->clearSiteCache($site);

        return response()->json(['success' => true]);
    }

    /**
     * Move an item to a different category (drag-drop across categories).
     */
    public function moveItem(Request $request, RestaurantSite $site, MenuItem $item)
    {
        $this->authorizeSite($site);
        $this->authorizeItem($site, $item);

        $data = $request->validate([
            'category_id' => ['required', 'integer'],
        ]);

        // Make sure the destination category belongs to this site too
        $newCategory = MenuCategory::where('id', $data['category_id'])
            ->where('restaurant_site_id', $site->id)
            ->first();

        if (!$newCategory) {
            return response()->json(['error' => 'Invalid destination category'], 422);
        }

        $item->menu_category_id = $newCategory->id;
        $item->save();

        $this->clearSiteCache($site);

        return response()->json([
            'success' => true,
            'item_id' => $item->id,
            'new_category_id' => $newCategory->id,
        ]);
    }

    /**
     * Upload an image for a menu item.
     */
    public function uploadItemImage(Request $request, RestaurantSite $site, MenuItem $item)
    {
        $this->authorizeSite($site);
        $this->authorizeItem($site, $item);

        $request->validate([
            'image' => ['required', 'image', 'mimes:jpeg,png,jpg,gif,webp', 'max:20480'],
        ]);

        // Delete old image if exists
        if ($item->image_path) {
            Storage::disk('public')->delete($item->image_path);
        }

        // Optimize: resize to max 800px wide and convert to WebP
        $file = $request->file('image');
        $slug = \Str::slug($item->name);
        $filename = $slug . '-' . time() . '.webp';
        $storagePath = $site->getStoragePath() . '/menu';
        $fullDir = Storage::disk('public')->path($storagePath);

        if (!is_dir($fullDir)) {
            mkdir($fullDir, 0775, true);
        }

        $srcImage = @imagecreatefromstring(file_get_contents($file->getRealPath()));
        if (!$srcImage) {
            // Fallback: store as-is if GD can't read the format
            $path = $file->store($storagePath, 'public');
            $item->update(['image_path' => $path]);
            $this->clearSiteCache($site);
            return $request->wantsJson()
                ? response()->json(['success' => true, 'image_url' => $item->fresh()->image_url])
                : back()->with('status', 'Item image uploaded successfully!');
        }

        $origW = imagesx($srcImage);
        $origH = imagesy($srcImage);
        $maxW = 800;

        if ($origW > $maxW) {
            $newW = $maxW;
            $newH = (int) round($origH * ($maxW / $origW));
            $resized = imagecreatetruecolor($newW, $newH);
            imagealphablending($resized, false);
            imagesavealpha($resized, true);
            imagecopyresampled($resized, $srcImage, 0, 0, 0, 0, $newW, $newH, $origW, $origH);
            imagedestroy($srcImage);
            $srcImage = $resized;
        }

        $outputPath = $fullDir . '/' . $filename;
        imagewebp($srcImage, $outputPath, 80);
        imagedestroy($srcImage);

        $path = $storagePath . '/' . $filename;
        $item->update(['image_path' => $path]);
        $this->clearSiteCache($site);

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'image_url' => $item->fresh()->image_url,
            ]);
        }

        return back()->with('status', 'Item image uploaded successfully!');
    }

    /**
     * Inline-update a single field (name or price) — for click-to-edit cells.
     */
    public function inlineUpdateItem(Request $request, RestaurantSite $site, MenuItem $item)
    {
        $this->authorizeSite($site);
        $this->authorizeItem($site, $item);

        $data = $request->validate([
            'field' => ['required', 'string', 'in:name,price'],
            'value' => ['required'],
        ]);

        if ($data['field'] === 'name') {
            $request->validate(['value' => ['required', 'string', 'max:120']]);
            $item->name = trim($data['value']);
        } elseif ($data['field'] === 'price') {
            // Strip $ and other non-numeric symbols before validating
            $cleaned = preg_replace('/[^\d.]/', '', (string) $data['value']);
            if (!is_numeric($cleaned) || $cleaned < 0) {
                return response()->json(['error' => 'Invalid price'], 422);
            }
            $item->price = (float) $cleaned;
        }

        $item->save();
        $this->clearSiteCache($site);

        return response()->json([
            'success' => true,
            'name' => $item->name,
            'price' => $item->price,
            'formatted_price' => $item->formatted_price,
        ]);
    }

    /**
     * Toggle active flag (sold-out / hidden quick action).
     */
    public function toggleItemActive(Request $request, RestaurantSite $site, MenuItem $item)
    {
        $this->authorizeSite($site);
        $this->authorizeItem($site, $item);

        $item->active = !$item->active;
        $item->save();
        $this->clearSiteCache($site);

        return response()->json([
            'success' => true,
            'active' => $item->active,
        ]);
    }

    /**
     * Duplicate a menu item (copy with same data, "(Copy)" appended to name).
     */
    public function duplicateItem(Request $request, RestaurantSite $site, MenuItem $item)
    {
        $this->authorizeSite($site);
        $this->authorizeItem($site, $item);

        $copy = $item->replicate(['image_path']); // Don't copy the image file (avoid double-pointing to same file)
        $copy->name = $this->buildCopyName($item->name, $item->menu_category_id);
        $copy->sort_order = $this->nextSortOrder($item->menu_category_id);
        $copy->save();

        $this->clearSiteCache($site);

        return response()->json([
            'success' => true,
            'item_id' => $copy->id,
            'name' => $copy->name,
            'reload' => true, // tell the front-end to refresh the page so the new item appears
        ]);
    }

    /**
     * Build a unique "(Copy)" name, handling repeated duplications cleanly.
     */
    protected function buildCopyName(string $original, int $categoryId): string
    {
        $base = preg_replace('/\s*\(Copy(?: \d+)?\)\s*$/', '', $original);
        $candidate = $base . ' (Copy)';
        $i = 2;
        while (MenuItem::where('menu_category_id', $categoryId)->where('name', $candidate)->exists()) {
            $candidate = $base . ' (Copy ' . $i . ')';
            $i++;
        }
        return $candidate;
    }

    protected function nextSortOrder(int $categoryId): int
    {
        return ((int) MenuItem::where('menu_category_id', $categoryId)->max('sort_order')) + 1;
    }

    /**
     * Toggle featured status for a menu item.
     */
    public function toggleItemFeatured(Request $request, RestaurantSite $site, MenuItem $item)
    {
        $this->authorizeSite($site);
        $this->authorizeItem($site, $item);

        $newStatus = $item->toggleFeatured();
        $this->clearSiteCache($site);

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'featured' => $newStatus,
            ]);
        }

        return back()->with('status', $newStatus ? 'Item marked as featured!' : 'Item removed from featured.');
    }

    /**
     * Verify the category belongs to this site.
     */
    protected function authorizeCategory(RestaurantSite $site, MenuCategory $category): void
    {
        if ($category->restaurant_site_id !== $site->id) {
            abort(403, 'This category does not belong to this restaurant site.');
        }
    }

    /**
     * Verify the item belongs to this site.
     */
    protected function authorizeItem(RestaurantSite $site, MenuItem $item): void
    {
        $category = $item->category;
        if (!$category || $category->restaurant_site_id !== $site->id) {
            abort(403, 'This item does not belong to this restaurant site.');
        }
    }

}
