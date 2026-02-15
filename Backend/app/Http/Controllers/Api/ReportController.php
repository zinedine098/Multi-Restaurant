<?php

namespace App\Http\Controllers\Api;

use App\Models\DailySalesSummary;
use App\Models\Order;
use App\Models\OrderItem;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ReportController extends BaseController
{
    /**
     * Get dashboard statistics.
     *
     * GET /api/reports/dashboard
     * Access: owner, admin (all) | manager (own branch)
     */
    public function dashboard(Request $request): JsonResponse
    {
        $user = $request->user();
        $restaurantId = $request->restaurant_id ?? $user->restaurant_id;

        // Owner/Admin can view all restaurants
        if (!$user->hasRole(['owner', 'admin'])) {
            $restaurantId = $user->restaurant_id;
        }

        $today = now()->toDateString();

        $todayOrders = Order::where('restaurant_id', $restaurantId)
            ->whereDate('created_at', $today);

        $data = [
            'today_orders' => (clone $todayOrders)->count(),
            'today_pending' => (clone $todayOrders)->where('status', 'pending')->count(),
            'today_completed' => (clone $todayOrders)->where('status', 'completed')->count(),
            'today_paid' => (clone $todayOrders)->where('status', 'paid')->count(),
            'today_cancelled' => (clone $todayOrders)->where('status', 'cancelled')->count(),
            'today_sales' => (clone $todayOrders)->where('status', 'paid')->sum('total_amount'),
        ];

        // Owner/Admin see aggregate across all restaurants
        if ($user->hasRole(['owner', 'admin']) && !$request->has('restaurant_id')) {
            $data['total_restaurants'] = \App\Models\Restaurant::count();
            $data['total_users'] = \App\Models\User::count();
            $data['today_orders'] = Order::whereDate('created_at', $today)->count();
            $data['today_sales'] = Order::whereDate('created_at', $today)
                ->where('status', 'paid')
                ->sum('total_amount');
        }

        return $this->sendResponse($data);
    }

    /**
     * Get daily sales report.
     *
     * GET /api/reports/daily-sales
     * Query: ?date_from=2026-02-01&date_to=2026-02-15&restaurant_id=1
     * Access: owner, admin (all) | manager (own branch)
     */
    public function dailySales(Request $request): JsonResponse
    {
        $user = $request->user();

        $query = Order::select(
            'restaurant_id',
            DB::raw('DATE(created_at) as date'),
            DB::raw('COUNT(*) as total_orders'),
            DB::raw("SUM(CASE WHEN status = 'paid' THEN total_amount ELSE 0 END) as gross_sales"),
            DB::raw("COUNT(CASE WHEN status = 'cancelled' THEN 1 END) as cancelled_orders"),
            DB::raw("COUNT(CASE WHEN status = 'paid' THEN 1 END) as paid_orders"),
        )
            ->groupBy('restaurant_id', DB::raw('DATE(created_at)'))
            ->orderByDesc('date');

        if ($user->hasRole(['owner', 'admin'])) {
            if ($request->has('restaurant_id')) {
                $query->where('restaurant_id', $request->restaurant_id);
            }
        } else {
            $query->where('restaurant_id', $user->restaurant_id);
        }

        if ($request->has('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }
        if ($request->has('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        $report = $query->get();

        return $this->sendResponse($report);
    }

    /**
     * Get popular menu items report.
     *
     * GET /api/reports/popular-items
     * Query: ?date_from=&date_to=&restaurant_id=&limit=10
     * Access: owner, admin, manager
     */
    public function popularItems(Request $request): JsonResponse
    {
        $user = $request->user();
        $limit = $request->get('limit', 10);

        $query = OrderItem::select(
            'menu_item_id',
            DB::raw('SUM(quantity) as total_quantity'),
            DB::raw('SUM(subtotal) as total_revenue'),
            DB::raw('COUNT(DISTINCT order_id) as order_count'),
        )
            ->join('orders', 'order_items.order_id', '=', 'orders.id')
            ->where('orders.status', '!=', 'cancelled')
            ->groupBy('menu_item_id')
            ->orderByDesc('total_quantity')
            ->limit($limit);

        if ($user->hasRole(['owner', 'admin'])) {
            if ($request->has('restaurant_id')) {
                $query->where('orders.restaurant_id', $request->restaurant_id);
            }
        } else {
            $query->where('orders.restaurant_id', $user->restaurant_id);
        }

        if ($request->has('date_from')) {
            $query->whereDate('orders.created_at', '>=', $request->date_from);
        }
        if ($request->has('date_to')) {
            $query->whereDate('orders.created_at', '<=', $request->date_to);
        }

        $items = $query->with('menuItem')->get();

        return $this->sendResponse($items);
    }
}
