<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\FoodOrder;
use App\Models\RestaurantSite;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Public (unauthenticated) kitchen display for demo restaurant sites.
 * Allows prospects to see orders appear in real time without a staff login.
 * Restricted to sites with status = 'demo'.
 */
class DemoKitchenController extends Controller
{
    /**
     * Render the demo kitchen display page.
     */
    public function show(string $slug)
    {
        $site = RestaurantSite::where('slug', $slug)
            ->where('status', RestaurantSite::STATUS_DEMO)
            ->firstOrFail();

        $alertMode = 'passive'; // Always passive for demo — no forced pop-ups

        return view('staff.orders.live', [
            'staff' => null,
            'site' => $site,
            'alertMode' => $alertMode,
            'demoMode' => true,
        ]);
    }

    /**
     * Render the demo server tablet (order-taking for waitstaff).
     */
    public function server(string $slug)
    {
        $site = RestaurantSite::where('slug', $slug)
            ->where('status', RestaurantSite::STATUS_DEMO)
            ->firstOrFail();

        return view('staff.server', [
            'staff' => null,
            'site' => $site,
            'demoMode' => true,
        ]);
    }

    /**
     * API: menu for the demo server tablet.
     */
    public function menu(string $slug): JsonResponse
    {
        $site = RestaurantSite::where('slug', $slug)
            ->where('status', RestaurantSite::STATUS_DEMO)
            ->first();

        if (!$site) return response()->json(['error' => 'Not found'], 404);

        $categories = $site->categories()
            ->where('active', true)
            ->with(['items' => fn ($q) => $q->where('active', true)->orderBy('sort_order')])
            ->orderBy('sort_order')
            ->get()
            ->map(fn ($cat) => [
                'id' => $cat->id,
                'name' => $cat->name,
                'items' => $cat->items->map(fn ($item) => [
                    'id' => $item->id,
                    'name' => $item->name,
                    'price' => (float) $item->price,
                    'formatted_price' => '$' . number_format($item->price, 2),
                    'description' => $item->description,
                ]),
            ]);

        return response()->json(['categories' => $categories]);
    }

    /**
     * API: create a dine-in order from the demo server tablet.
     */
    public function storeOrder(Request $request, string $slug): JsonResponse
    {
        $site = RestaurantSite::where('slug', $slug)
            ->where('status', RestaurantSite::STATUS_DEMO)
            ->first();

        if (!$site) return response()->json(['error' => 'Not found'], 404);

        $data = $request->validate([
            'table_number' => ['required', 'string', 'max:20'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.menu_item_id' => ['required', 'integer'],
            'items.*.quantity' => ['required', 'integer', 'min:1', 'max:99'],
            'special_instructions' => ['nullable', 'string', 'max:1000'],
        ]);

        $orderingSettings = $site->getOrderingSettings();
        $subtotal = 0;
        $orderItems = [];

        foreach ($data['items'] as $itemData) {
            $menuItem = \App\Models\MenuItem::whereHas('category', fn ($q) => $q->where('restaurant_site_id', $site->id)->where('active', true))
                ->where('id', $itemData['menu_item_id'])->where('active', true)->first();
            if (!$menuItem) continue;

            $itemTotal = $menuItem->price * $itemData['quantity'];
            $subtotal += $itemTotal;
            $orderItems[] = [
                'menu_item_id' => $menuItem->id, 'name' => $menuItem->name,
                'price' => $menuItem->price, 'quantity' => $itemData['quantity'],
                'total' => $itemTotal,
            ];
        }

        $taxRate = $orderingSettings['tax_rate'] ?? 0;
        $taxAmount = $subtotal * $taxRate;

        $order = FoodOrder::create([
            'restaurant_site_id' => $site->id,
            'status' => FoodOrder::STATUS_CONFIRMED,
            'order_type' => FoodOrder::TYPE_DINE_IN,
            'table_number' => $data['table_number'],
            'customer_name' => 'Table ' . $data['table_number'],
            'is_asap' => true,
            'subtotal' => $subtotal,
            'tax_amount' => $taxAmount,
            'tax_rate' => $taxRate,
            'delivery_fee' => 0,
            'total' => $subtotal + $taxAmount,
            'confirmed_at' => now(),
            'estimated_ready_at' => now()->addMinutes((int) ($orderingSettings['estimated_prep_time_minutes'] ?? 30)),
            'special_instructions' => $data['special_instructions'] ?? null,
            'payment_status' => 'unpaid',
            'metadata' => ['source' => 'demo_server_tablet'],
        ]);

        foreach ($orderItems as $item) {
            $order->items()->create($item);
        }

        return response()->json([
            'success' => true,
            'order' => [
                'id' => $order->id,
                'order_number' => $order->order_number,
                'table_number' => $order->table_number,
                'status' => $order->status,
                'status_label' => $order->status_label,
                'total' => number_format($order->total, 2),
                'item_count' => collect($orderItems)->sum('quantity'),
            ],
        ], 201);
    }

    /**
     * API: add items to an existing demo order.
     */
    public function addItemsToOrder(Request $request, string $slug, FoodOrder $order): JsonResponse
    {
        $site = RestaurantSite::where('slug', $slug)
            ->where('status', RestaurantSite::STATUS_DEMO)
            ->first();

        if (!$site || $order->restaurant_site_id !== $site->id) {
            return response()->json(['error' => 'Not found'], 404);
        }

        $data = $request->validate([
            'items' => ['required', 'array', 'min:1'],
            'items.*.menu_item_id' => ['required', 'integer'],
            'items.*.quantity' => ['required', 'integer', 'min:1', 'max:99'],
        ]);

        foreach ($data['items'] as $itemData) {
            $menuItem = \App\Models\MenuItem::whereHas('category', fn ($q) => $q->where('restaurant_site_id', $site->id)->where('active', true))
                ->where('id', $itemData['menu_item_id'])->where('active', true)->first();
            if (!$menuItem) continue;

            $order->items()->create([
                'menu_item_id' => $menuItem->id, 'name' => $menuItem->name,
                'price' => $menuItem->price, 'quantity' => $itemData['quantity'],
                'total' => $menuItem->price * $itemData['quantity'],
            ]);
        }

        $order->load('items');
        $order->calculateTotals();
        $order->save();

        return response()->json(['success' => true, 'order' => ['id' => $order->id, 'total' => number_format($order->total, 2)]]);
    }

    /**
     * API: list orders for a demo site (public, no auth).
     *
     * GET /api/demo-kitchen/{slug}/orders?status=active&since=...
     */
    public function orders(Request $request, string $slug): JsonResponse
    {
        $site = RestaurantSite::where('slug', $slug)
            ->where('status', RestaurantSite::STATUS_DEMO)
            ->first();

        if (!$site) {
            return response()->json(['error' => 'Not found'], 404);
        }

        $query = FoodOrder::where('restaurant_site_id', $site->id)
            ->with('items');

        $statusFilter = $request->input('status', 'active');
        if ($statusFilter === 'active') {
            $query->active();
        } elseif ($statusFilter === 'completed') {
            $query->whereIn('status', [FoodOrder::STATUS_COMPLETED, FoodOrder::STATUS_CANCELLED])
                ->whereDate('created_at', today());
        } elseif ($statusFilter === 'all') {
            $query->today();
        }

        if ($request->filled('since')) {
            $query->where('updated_at', '>=', \Carbon\Carbon::parse($request->input('since')));
        }

        $orders = $query->orderBy('created_at', 'desc')
            ->limit(50)
            ->get()
            ->map(function ($order) {
                return [
                    'id' => $order->id,
                    'order_number' => $order->order_number,
                    'status' => $order->status,
                    'status_label' => $order->status_label,
                    'payment_status' => $order->payment_status,
                    'customer_name' => $order->customer_name,
                    'customer_phone' => $order->customer_phone,
                    'order_type' => $order->order_type,
                    'order_type_label' => $order->order_type_label,
                    'table_number' => $order->table_number,
                    'staff_id' => $order->staff_id,
                    'is_asap' => $order->is_asap,
                    'scheduled_for' => $order->scheduled_for?->toIso8601String(),
                    'special_instructions' => $order->special_instructions,
                    'delivery_address' => $order->delivery_address,
                    'items' => $order->items->map(fn ($item) => [
                        'name' => $item->name,
                        'quantity' => $item->quantity,
                        'price' => number_format($item->price, 2),
                        'total' => number_format($item->total, 2),
                        'special_requests' => $item->special_requests,
                    ]),
                    'subtotal' => number_format($order->subtotal, 2),
                    'tax_amount' => number_format($order->tax_amount, 2),
                    'delivery_fee' => $order->delivery_fee > 0 ? number_format($order->delivery_fee, 2) : null,
                    'total' => number_format($order->total, 2),
                    'item_count' => $order->items->sum('quantity'),
                    'created_at' => $order->created_at->toIso8601String(),
                    'updated_at' => $order->updated_at->toIso8601String(),
                    'estimated_ready_at' => $order->estimated_ready_at?->toIso8601String(),
                    'elapsed_minutes' => $order->created_at->diffInMinutes(now()),
                ];
            });

        return response()->json([
            'orders' => $orders,
            'server_time' => now()->toIso8601String(),
        ]);
    }

    /**
     * Update order status from the demo kitchen display.
     * Only works for orders belonging to demo sites.
     *
     * POST /api/demo-kitchen/{slug}/orders/{order}/status
     * { "action": "confirm"|"preparing"|"ready"|"complete"|"cancel", "reason": "..." }
     */
    public function updateStatus(Request $request, string $slug, FoodOrder $order): JsonResponse
    {
        $site = RestaurantSite::where('slug', $slug)
            ->where('status', RestaurantSite::STATUS_DEMO)
            ->first();

        if (!$site) {
            return response()->json(['error' => 'Not found'], 404);
        }

        if ($order->restaurant_site_id !== $site->id) {
            return response()->json(['error' => 'Order does not belong to this restaurant'], 403);
        }

        $action = $request->input('action');
        $success = false;

        switch ($action) {
            case 'confirm':
                $settings = $site->getOrderingSettings();
                $prepTime = (int) ($settings['estimated_prep_time_minutes'] ?? 30);
                $success = $order->confirm($prepTime);
                break;
            case 'preparing':
                $success = $order->startPreparing();
                break;
            case 'ready':
                $success = $order->markReady();
                break;
            case 'complete':
                $success = $order->complete();
                break;
            case 'cancel':
                $reason = $request->input('reason', 'Cancelled from demo kitchen');
                $success = $order->cancel($reason);
                break;
            default:
                return response()->json(['error' => 'Invalid action'], 422);
        }

        if (!$success) {
            return response()->json(['error' => 'Cannot perform this action on the current order status'], 409);
        }

        return response()->json([
            'success' => true,
            'order' => [
                'id' => $order->id,
                'status' => $order->status,
                'status_label' => $order->status_label,
                'updated_at' => $order->updated_at->toIso8601String(),
            ],
        ]);
    }
}
