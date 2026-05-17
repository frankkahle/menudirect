<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Models\FoodOrder;
use Illuminate\Http\Request;

class StaffDashboardController extends Controller
{
    /**
     * Staff dashboard — shows their assigned restaurant's orders at a glance.
     */
    public function index(Request $request)
    {
        $staff = auth('staff')->user();
        $site = $staff->site;

        $pendingCount = $site->foodOrders()->where('status', FoodOrder::STATUS_PENDING)->count();
        $activeCount = $site->foodOrders()->whereIn('status', [
            FoodOrder::STATUS_CONFIRMED,
            FoodOrder::STATUS_PREPARING,
            FoodOrder::STATUS_READY,
        ])->count();
        $todayCompleted = $site->foodOrders()
            ->where('status', FoodOrder::STATUS_COMPLETED)
            ->whereDate('updated_at', today())
            ->count();

        $recentOrders = $site->foodOrders()
            ->with('items')
            ->latest()
            ->limit(20)
            ->get();

        return view('staff.dashboard', compact('staff', 'site', 'pendingCount', 'activeCount', 'todayCompleted', 'recentOrders'));
    }
}
