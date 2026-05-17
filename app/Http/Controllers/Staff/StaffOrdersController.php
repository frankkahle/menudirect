<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Jobs\SendOrderNotificationsJob;
use App\Models\FoodOrder;
use Illuminate\Http\Request;

class StaffOrdersController extends Controller
{
    /**
     * Ensure the order belongs to the staff member's assigned restaurant.
     */
    protected function authorizeOrder(FoodOrder $order): void
    {
        $staff = auth('staff')->user();
        if (!$staff->canManageOrders() || $order->restaurant_site_id !== $staff->restaurant_site_id) {
            abort(403, 'You are not authorized to manage this order.');
        }
    }

    /**
     * Tablet order screen — standalone full-screen view.
     */
    /**
     * Server tablet — waitstaff order-taking screen.
     */
    public function server()
    {
        $staff = auth('staff')->user();
        $site = $staff->site;

        return view('staff.server', compact('staff', 'site'));
    }

    public function live()
    {
        $staff = auth('staff')->user();
        $site = $staff->site;
        $orderingSettings = $site->getOrderingSettings();
        $alertMode = $orderingSettings['new_order_alert_mode'] ?? 'forced';

        return view('staff.orders.live', compact('staff', 'site', 'alertMode'));
    }

    public function index(Request $request)
    {
        $staff = auth('staff')->user();
        $query = $staff->site->foodOrders()->with('items');

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $orders = $query->latest()->paginate(30);

        return view('staff.orders.index', compact('staff', 'orders'));
    }

    public function show(FoodOrder $order)
    {
        $this->authorizeOrder($order);
        $order->load('items');

        return view('staff.orders.show', compact('order'));
    }

    public function confirm(Request $request, FoodOrder $order)
    {
        $this->authorizeOrder($order);
        $order->confirm();
        SendOrderNotificationsJob::dispatch($order, 'confirmed');
        return back()->with('status', 'Order confirmed. Customer notified.');
    }

    public function preparing(Request $request, FoodOrder $order)
    {
        $this->authorizeOrder($order);
        $order->startPreparing();
        return back()->with('status', 'Order marked as preparing.');
    }

    public function ready(Request $request, FoodOrder $order)
    {
        $this->authorizeOrder($order);
        $order->markReady();
        SendOrderNotificationsJob::dispatch($order, 'ready');
        return back()->with('status', 'Order marked ready. Customer notified.');
    }

    public function complete(Request $request, FoodOrder $order)
    {
        $this->authorizeOrder($order);
        $order->complete();
        return back()->with('status', 'Order completed.');
    }

    public function cancel(Request $request, FoodOrder $order)
    {
        $this->authorizeOrder($order);
        $request->validate(['reason' => 'required|string|max:255']);
        $order->cancel($request->reason);
        SendOrderNotificationsJob::dispatch($order, 'cancelled');
        return back()->with('status', 'Order cancelled. Customer notified.');
    }
}
