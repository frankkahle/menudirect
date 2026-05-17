<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\FoodOrder;
use App\Models\FoodOrderItem;
use App\Models\MenuItem;
use App\Models\OrderAuditLog;
use App\Models\ShiftCloseout;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Authenticated API for the tablet order screen.
 * Uses the staff session cookie for auth (same-origin requests from /staff/orders/live).
 */
class StaffOrdersApiController extends Controller
{
    /**
     * List orders for the authenticated staff member's restaurant.
     * Supports incremental polling via ?since= timestamp.
     *
     * GET /api/staff/orders?status=active&since=2026-04-12T10:00:00Z
     */
    public function index(Request $request): JsonResponse
    {
        $staff = auth('staff')->user();

        if (!$staff || !$staff->is_active) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $query = FoodOrder::where('restaurant_site_id', $staff->restaurant_site_id)
            ->with('items');

        // Filter by status group
        $statusFilter = $request->input('status', 'active');
        if ($statusFilter === 'active') {
            $query->active();
        } elseif ($statusFilter === 'completed') {
            $query->whereIn('status', [FoodOrder::STATUS_COMPLETED, FoodOrder::STATUS_CANCELLED])
                ->whereDate('created_at', today());
        }
        // 'all' = today's orders, no status filter
        if ($statusFilter === 'all') {
            $query->today();
        }

        // Incremental polling: only return orders updated since the given timestamp
        if ($request->filled('since')) {
            $query->where('updated_at', '>=', \Carbon\Carbon::parse($request->input('since')));
        }

        $orders = $query->orderBy('created_at', 'desc')
            ->limit(100)
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
     * Get the restaurant's menu for the staff order screen.
     */
    public function menu(Request $request): JsonResponse
    {
        $staff = auth('staff')->user();
        if (!$staff || !$staff->is_active) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $site = $staff->site;
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
     * Create a dine-in order from the tablet.
     *
     * POST /api/staff/orders
     * { "table_number": "4", "items": [{ "menu_item_id": 1, "quantity": 2, "special_requests": "..." }] }
     */
    public function store(Request $request): JsonResponse
    {
        $staff = auth('staff')->user();
        if (!$staff || !$staff->is_active) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $data = $request->validate([
            'table_number' => ['required', 'string', 'max:20'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.menu_item_id' => ['required', 'integer'],
            'items.*.quantity' => ['required', 'integer', 'min:1', 'max:99'],
            'items.*.special_requests' => ['nullable', 'string', 'max:255'],
            'special_instructions' => ['nullable', 'string', 'max:1000'],
            'customer_name' => ['nullable', 'string', 'max:255'],
        ]);

        $site = $staff->site;
        $orderingSettings = $site->getOrderingSettings();

        // Calculate totals server-side
        $subtotal = 0;
        $orderItems = [];

        foreach ($data['items'] as $itemData) {
            $menuItem = MenuItem::whereHas('category', fn ($q) => $q->where('restaurant_site_id', $site->id)->where('active', true))
                ->where('id', $itemData['menu_item_id'])
                ->where('active', true)
                ->first();

            if (!$menuItem) {
                return response()->json(['error' => 'Menu item not found or unavailable'], 400);
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

        $taxRate = $orderingSettings['tax_rate'] ?? 0;
        $taxAmount = $subtotal * $taxRate;
        $total = $subtotal + $taxAmount;

        try {
            DB::connection('menudirect')->beginTransaction();

            $order = FoodOrder::create([
                'restaurant_site_id' => $site->id,
                'staff_id' => $staff->id,
                'status' => FoodOrder::STATUS_CONFIRMED,
                'order_type' => FoodOrder::TYPE_DINE_IN,
                'table_number' => $data['table_number'],
                'customer_name' => $data['customer_name'] ?? 'Table ' . $data['table_number'],
                'is_asap' => true,
                'subtotal' => $subtotal,
                'tax_amount' => $taxAmount,
                'tax_rate' => $taxRate,
                'delivery_fee' => 0,
                'total' => $total,
                'estimated_ready_at' => now()->addMinutes((int) ($orderingSettings['estimated_prep_time_minutes'] ?? 30)),
                'confirmed_at' => now(),
                'special_instructions' => $data['special_instructions'] ?? null,
                'payment_status' => 'unpaid',
                'metadata' => [
                    'source' => 'staff_tablet',
                    'staff_name' => $staff->name,
                ],
            ]);

            foreach ($orderItems as $item) {
                $order->items()->create($item);
            }

            DB::connection('menudirect')->commit();

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

        } catch (\Exception $e) {
            DB::connection('menudirect')->rollBack();
            \Log::error("Staff order creation failed: {$e->getMessage()}");
            return response()->json(['error' => 'Failed to create order'], 500);
        }
    }

    /**
     * Add items to an existing open dine-in order (add to tab).
     *
     * POST /api/staff/orders/{order}/add-items
     */
    public function addItems(Request $request, FoodOrder $order): JsonResponse
    {
        $staff = auth('staff')->user();
        if (!$staff || !$staff->is_active) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }
        if ($order->restaurant_site_id !== $staff->restaurant_site_id) {
            return response()->json(['error' => 'Forbidden'], 403);
        }
        if (!$order->isActive()) {
            return response()->json(['error' => 'Order is not active'], 409);
        }

        $data = $request->validate([
            'items' => ['required', 'array', 'min:1'],
            'items.*.menu_item_id' => ['required', 'integer'],
            'items.*.quantity' => ['required', 'integer', 'min:1', 'max:99'],
            'items.*.special_requests' => ['nullable', 'string', 'max:255'],
        ]);

        $site = $staff->site;

        foreach ($data['items'] as $itemData) {
            $menuItem = MenuItem::whereHas('category', fn ($q) => $q->where('restaurant_site_id', $site->id)->where('active', true))
                ->where('id', $itemData['menu_item_id'])
                ->where('active', true)
                ->first();

            if (!$menuItem) continue;

            $order->items()->create([
                'menu_item_id' => $menuItem->id,
                'name' => $menuItem->name,
                'price' => $menuItem->price,
                'quantity' => $itemData['quantity'],
                'special_requests' => $itemData['special_requests'] ?? null,
                'total' => $menuItem->price * $itemData['quantity'],
            ]);
        }

        // Recalculate totals
        $order->load('items');
        $order->calculateTotals();
        $order->save();

        return response()->json([
            'success' => true,
            'order' => [
                'id' => $order->id,
                'total' => number_format($order->total, 2),
                'item_count' => $order->items->sum('quantity'),
            ],
        ]);
    }

    /**
     * Update order status from the tablet screen.
     *
     * POST /api/staff/orders/{order}/status
     * { "action": "confirm"|"preparing"|"ready"|"complete"|"cancel", "reason": "..." }
     */
    public function updateStatus(Request $request, FoodOrder $order): JsonResponse
    {
        $staff = auth('staff')->user();

        if (!$staff || !$staff->is_active) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        if ($order->restaurant_site_id !== $staff->restaurant_site_id) {
            return response()->json(['error' => 'Forbidden'], 403);
        }

        $action = $request->input('action');
        $success = false;

        switch ($action) {
            case 'confirm':
                $settings = $order->restaurantSite->getOrderingSettings();
                $prepTime = (int) ($settings['estimated_prep_time_minutes'] ?? 30);
                $success = $order->confirm($prepTime);
                break;
            case 'preparing':
                $success = $order->startPreparing();
                break;
            case 'ready':
                $success = $order->markReady();
                if ($success) {
                    \App\Jobs\SendOrderNotificationsJob::dispatch($order, 'status_update', 'preparing');
                }
                break;
            case 'complete':
                $success = $order->complete();
                break;
            case 'cancel':
                $reason = $request->input('reason', 'Cancelled by staff');
                $success = $order->cancel($reason);
                if ($success) {
                    \App\Jobs\SendOrderNotificationsJob::dispatch($order, 'status_update', $order->getOriginal('status'));
                }
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

    /**
     * Get audit log for a specific order.
     */
    public function auditLog(FoodOrder $order): JsonResponse
    {
        $staff = auth('staff')->user();
        if (!$staff || !$staff->is_active) return response()->json(['error' => 'Unauthorized'], 401);
        if ($order->restaurant_site_id !== $staff->restaurant_site_id) return response()->json(['error' => 'Forbidden'], 403);

        $logs = $order->auditLogs()->with('staff:id,name')->orderBy('created_at')->get()->map(fn ($log) => [
            'action' => $log->action,
            'from_status' => $log->from_status,
            'to_status' => $log->to_status,
            'source' => $log->source,
            'staff_name' => $log->staff?->name,
            'note' => $log->note,
            'metadata' => $log->metadata,
            'created_at' => $log->created_at->toIso8601String(),
            'time_label' => $log->created_at->format('g:i A'),
        ]);

        return response()->json(['logs' => $logs]);
    }

    /**
     * Get shift summary for a given date (preview before closing).
     */
    public function shiftSummary(Request $request): JsonResponse
    {
        $staff = auth('staff')->user();
        if (!$staff || !$staff->is_active) return response()->json(['error' => 'Unauthorized'], 401);

        $date = $request->input('date', today()->toDateString());
        $site = $staff->site;

        $orders = FoodOrder::where('restaurant_site_id', $site->id)
            ->whereDate('created_at', $date)
            ->get();

        $active = $orders->whereIn('status', [FoodOrder::STATUS_PENDING, FoodOrder::STATUS_CONFIRMED, FoodOrder::STATUS_PREPARING, FoodOrder::STATUS_READY]);
        $completed = $orders->whereIn('status', [FoodOrder::STATUS_COMPLETED, FoodOrder::STATUS_READY, FoodOrder::STATUS_CONFIRMED, FoodOrder::STATUS_PREPARING]);
        $cancelled = $orders->where('status', FoodOrder::STATUS_CANCELLED);

        return response()->json([
            'date' => $date,
            'has_active_orders' => $active->count() > 0,
            'active_count' => $active->count(),
            'summary' => [
                'total_orders' => $completed->count(),
                'dine_in' => $completed->where('order_type', FoodOrder::TYPE_DINE_IN)->count(),
                'pickup' => $completed->where('order_type', FoodOrder::TYPE_PICKUP)->count(),
                'delivery' => $completed->where('order_type', FoodOrder::TYPE_DELIVERY)->count(),
                'cancelled' => $cancelled->count(),
                'gross_sales' => number_format($completed->sum('subtotal'), 2),
                'tax_collected' => number_format($completed->sum('tax_amount'), 2),
                'delivery_fees' => number_format($completed->sum('delivery_fee'), 2),
                'total_revenue' => number_format($completed->sum('total'), 2),
                'cash_total' => number_format($completed->where('payment_status', '!=', 'paid')->sum('total'), 2),
                'card_total' => number_format($completed->where('payment_status', 'paid')->sum('total'), 2),
            ],
            'already_closed' => ShiftCloseout::where('restaurant_site_id', $site->id)->where('shift_date', $date)->exists(),
        ]);
    }

    /**
     * Close out a shift — saves the daily summary.
     */
    public function closeShift(Request $request): JsonResponse
    {
        $staff = auth('staff')->user();
        if (!$staff || !$staff->is_active || !$staff->isManager()) {
            return response()->json(['error' => 'Only managers can close shifts'], 403);
        }

        $date = $request->input('date', today()->toDateString());
        $notes = $request->input('notes');
        $site = $staff->site;

        // Check if already closed
        if (ShiftCloseout::where('restaurant_site_id', $site->id)->where('shift_date', $date)->exists()) {
            return response()->json(['error' => 'This shift has already been closed'], 409);
        }

        // Auto-complete all remaining ready orders
        FoodOrder::where('restaurant_site_id', $site->id)
            ->whereDate('created_at', $date)
            ->where('status', FoodOrder::STATUS_READY)
            ->each(fn ($order) => $order->complete());

        $closeout = ShiftCloseout::generateForDate($site, $date, $staff->id, $notes);

        return response()->json([
            'success' => true,
            'closeout' => [
                'id' => $closeout->id,
                'shift_date' => $closeout->shift_date->format('M j, Y'),
                'total_orders' => $closeout->total_orders,
                'total_revenue' => number_format($closeout->total_revenue, 2),
                'cash_total' => number_format($closeout->cash_total, 2),
                'card_total' => number_format($closeout->card_total, 2),
            ],
        ]);
    }
}
