<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Client\Traits\AuthorizesRestaurantSite;
use App\Jobs\SendOrderNotificationsJob;
use App\Models\FoodOrder;
use App\Models\RestaurantSite;
use Illuminate\Http\Request;

class RestaurantOrdersController extends Controller
{
    use AuthorizesRestaurantSite;

    /**
     * Display a listing of orders for the restaurant.
     */
    public function index(Request $request, RestaurantSite $site)
    {
        $this->authorizeSite($site);

        $query = $site->foodOrders()->with('items')->latest();

        // Filter by status
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Filter by date
        if ($request->filled('date')) {
            $query->whereDate('created_at', $request->date);
        } elseif (!$request->filled('status') && !$request->filled('all')) {
            // Default to today's orders
            $query->whereDate('created_at', today());
        }

        $orders = $query->paginate(20);

        // Get counts for badges
        $counts = [
            'pending' => $site->foodOrders()->pending()->count(),
            'confirmed' => $site->foodOrders()->whereIn('status', [FoodOrder::STATUS_CONFIRMED, FoodOrder::STATUS_PREPARING])->count(),
            'ready' => $site->foodOrders()->ready()->count(),
            'today' => $site->foodOrders()->today()->count(),
        ];

        return view('client.restaurant.orders.index', compact('site', 'orders', 'counts'));
    }

    /**
     * Display the specified order.
     */
    public function show(RestaurantSite $site, FoodOrder $order)
    {
        $this->authorizeSite($site);
        $this->authorizeOrder($site, $order);

        $order->load(['items', 'notifications']);

        return view('client.restaurant.orders.show', compact('site', 'order'));
    }

    /**
     * Confirm an order.
     */
    public function confirm(Request $request, RestaurantSite $site, FoodOrder $order)
    {
        $this->authorizeSite($site);
        $this->authorizeOrder($site, $order);

        if (!$order->isPending()) {
            return back()->with('error', 'This order cannot be confirmed.');
        }

        $prepTime = $request->input('prep_time', $site->getOrderingSettings()['estimated_prep_time_minutes'] ?? 30);

        $previousStatus = $order->status;
        $order->confirm((int) $prepTime);

        // Send notifications
        SendOrderNotificationsJob::dispatch($order, 'status_update', $previousStatus);

        return back()->with('status', 'Order confirmed! Customer has been notified.');
    }

    /**
     * Mark an order as preparing.
     */
    public function preparing(RestaurantSite $site, FoodOrder $order)
    {
        $this->authorizeSite($site);
        $this->authorizeOrder($site, $order);

        if (!in_array($order->status, [FoodOrder::STATUS_PENDING, FoodOrder::STATUS_CONFIRMED])) {
            return back()->with('error', 'This order cannot be marked as preparing.');
        }

        $previousStatus = $order->status;
        $order->startPreparing();

        // Send notifications
        SendOrderNotificationsJob::dispatch($order, 'status_update', $previousStatus);

        return back()->with('status', 'Order marked as preparing.');
    }

    /**
     * Mark an order as ready.
     */
    public function ready(RestaurantSite $site, FoodOrder $order)
    {
        $this->authorizeSite($site);
        $this->authorizeOrder($site, $order);

        if (!in_array($order->status, [FoodOrder::STATUS_CONFIRMED, FoodOrder::STATUS_PREPARING])) {
            return back()->with('error', 'This order cannot be marked as ready.');
        }

        $previousStatus = $order->status;
        $order->markReady();

        // Send notifications
        SendOrderNotificationsJob::dispatch($order, 'status_update', $previousStatus);

        return back()->with('status', 'Order marked as ready! Customer has been notified.');
    }

    /**
     * Mark an order as completed.
     */
    public function complete(RestaurantSite $site, FoodOrder $order)
    {
        $this->authorizeSite($site);
        $this->authorizeOrder($site, $order);

        if (!$order->isReady()) {
            return back()->with('error', 'This order cannot be marked as completed.');
        }

        $previousStatus = $order->status;
        $order->complete();

        // Send notifications
        SendOrderNotificationsJob::dispatch($order, 'status_update', $previousStatus);

        return back()->with('status', 'Order completed!');
    }

    /**
     * Cancel an order.
     */
    public function cancel(Request $request, RestaurantSite $site, FoodOrder $order)
    {
        $this->authorizeSite($site);
        $this->authorizeOrder($site, $order);

        if (in_array($order->status, [FoodOrder::STATUS_COMPLETED, FoodOrder::STATUS_CANCELLED])) {
            return back()->with('error', 'This order cannot be cancelled.');
        }

        $reason = $request->input('reason', 'Cancelled by restaurant');

        $previousStatus = $order->status;
        $order->cancel($reason);

        // Send notifications
        SendOrderNotificationsJob::dispatch($order, 'status_update', $previousStatus);

        return back()->with('status', 'Order cancelled. Customer has been notified.');
    }

    /**
     * Update the estimated ready time.
     */
    public function updateEstimate(Request $request, RestaurantSite $site, FoodOrder $order)
    {
        $this->authorizeSite($site);
        $this->authorizeOrder($site, $order);

        $request->validate([
            'estimated_ready_at' => ['required', 'date', 'after:now'],
        ]);

        $order->update([
            'estimated_ready_at' => $request->estimated_ready_at,
        ]);

        return back()->with('status', 'Estimated ready time updated.');
    }

    /**
     * Verify the order belongs to the site.
     */
    protected function authorizeOrder(RestaurantSite $site, FoodOrder $order): void
    {
        if ($order->restaurant_site_id !== $site->id) {
            abort(404, 'Order not found.');
        }
    }

    /**
     * Poll for new/updated orders since a given timestamp.
     * Used by the dashboard JS for near-real-time order awareness.
     */
    public function poll(Request $request, RestaurantSite $site)
    {
        $this->authorizeSite($site);

        $since = $request->input('since');
        $sinceDate = $since ? \Carbon\Carbon::parse($since) : now()->subMinutes(5);

        $orders = $site->foodOrders()
            ->where('updated_at', '>', $sinceDate)
            ->with('items')
            ->latest('updated_at')
            ->get()
            ->map(fn($order) => [
                'id' => $order->id,
                'order_number' => $order->order_number,
                'status' => $order->status,
                'customer_name' => $order->customer_name,
                'order_type' => $order->order_type,
                'total' => $order->total,
                'items_count' => $order->items->count(),
                'created_at' => $order->created_at->toIso8601String(),
                'updated_at' => $order->updated_at->toIso8601String(),
            ]);

        $pendingCount = $site->foodOrders()->where('status', FoodOrder::STATUS_PENDING)->count();

        return response()->json([
            'orders' => $orders,
            'pending_count' => $pendingCount,
            'polled_at' => now()->toIso8601String(),
        ]);
    }
}
