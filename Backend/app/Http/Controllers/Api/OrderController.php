<?php

namespace App\Http\Controllers\Api;

use App\Models\Notification;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\OrderStatusLog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class OrderController extends BaseController
{
    /**
     * Display a listing of orders.
     *
     * GET /api/orders
     * Access: all authenticated (scoped to restaurant)
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        $query = Order::with(['orderItems.menuItem', 'user']);

        if ($user->hasRole(['owner', 'admin'])) {
            if ($request->has('restaurant_id')) {
                $query->where('restaurant_id', $request->restaurant_id);
            }
        } else {
            $query->where('restaurant_id', $user->restaurant_id);
        }

        // Filter by status
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        // Filter by date range
        if ($request->has('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }
        if ($request->has('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        $perPage = $request->get('per_page', 15);
        $orders = $query->orderByDesc('created_at')->paginate($perPage);

        return $this->sendPaginatedResponse($orders);
    }

    /**
     * Store a newly created order.
     *
     * POST /api/orders
     * Access: waiter only
     *
     * Body:
     * {
     *   "customer_name": "Budi",
     *   "customer_phone": "08123456789",  // optional
     *   "notes": "Jangan pakai bawang",   // optional
     *   "items": [
     *     { "menu_item_id": 1, "quantity": 2, "notes": "Pedas" },
     *     { "menu_item_id": 3, "quantity": 1 }
     *   ]
     * }
     */
    public function store(Request $request): JsonResponse
    {
        $user = $request->user();

        $validator = Validator::make($request->all(), [
            'customer_name' => 'required|string|max:255',
            'customer_phone' => 'nullable|string|max:20',
            'notes' => 'nullable|string',
            'items' => 'required|array|min:1',
            'items.*.menu_item_id' => 'required|exists:menu_items,id',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.notes' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return $this->sendError('Validation error.', $validator->errors(), 422);
        }

        try {
            DB::beginTransaction();

            // Create order
            $order = Order::create([
                'restaurant_id' => $user->restaurant_id,
                'user_id' => $user->id,
                'customer_name' => $request->customer_name,
                'customer_phone' => $request->customer_phone,
                'order_number' => Order::generateOrderNumber($user->restaurant_id),
                'status' => 'pending',
                'notes' => $request->notes,
            ]);

            // Create order items
            $totalAmount = 0;
            foreach ($request->items as $item) {
                $menuItem = \App\Models\MenuItem::findOrFail($item['menu_item_id']);

                if (!$menuItem->is_available) {
                    DB::rollBack();
                    return $this->sendError("Menu '{$menuItem->name}' sedang tidak tersedia.", null, 422);
                }

                $subtotal = $menuItem->price * $item['quantity'];
                $totalAmount += $subtotal;

                OrderItem::create([
                    'order_id' => $order->id,
                    'menu_item_id' => $item['menu_item_id'],
                    'quantity' => $item['quantity'],
                    'price_at_time' => $menuItem->price,
                    'subtotal' => $subtotal,
                    'notes' => $item['notes'] ?? null,
                ]);
            }

            // Update total amount
            $order->update(['total_amount' => $totalAmount]);

            // Create initial status log
            OrderStatusLog::create([
                'order_id' => $order->id,
                'old_status' => null,
                'new_status' => 'pending',
                'changed_by' => $user->id,
                'changed_at' => now(),
                'notes' => 'Order dibuat oleh pelayan.',
            ]);

            // Notify kitchen staff
            $kitchenUsers = \App\Models\User::where('restaurant_id', $user->restaurant_id)
                ->where('role', 'kitchen')
                ->where('is_active', true)
                ->get();

            foreach ($kitchenUsers as $kitchenUser) {
                Notification::create([
                    'restaurant_id' => $user->restaurant_id,
                    'user_id' => $kitchenUser->id,
                    'type' => 'new_order',
                    'title' => 'Pesanan Baru!',
                    'message' => "Order #{$order->order_number} dari {$order->customer_name}",
                    'data' => ['order_id' => $order->id],
                    'created_at' => now(),
                ]);
            }

            DB::commit();

            $order->load(['orderItems.menuItem', 'user']);

            return $this->sendResponse($order, 'Order berhasil dibuat.', 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->sendError('Gagal membuat order.', $e->getMessage(), 500);
        }
    }

    /**
     * Display the specified order.
     *
     * GET /api/orders/{order}
     */
    public function show(Request $request, Order $order): JsonResponse
    {
        $user = $request->user();

        if (!$user->hasRole(['owner', 'admin']) && $user->restaurant_id !== $order->restaurant_id) {
            return $this->sendError('Anda tidak memiliki akses.', null, 403);
        }

        $order->load(['orderItems.menuItem', 'user', 'statusLogs.changedByUser']);

        return $this->sendResponse($order);
    }

    /**
     * Update order status (Kitchen: pending → completed).
     *
     * PATCH /api/orders/{order}/status
     * Access: kitchen (pending → completed)
     *
     * Body: { "status": "completed" }
     */
    public function updateStatus(Request $request, Order $order): JsonResponse
    {
        $user = $request->user();

        if ($user->restaurant_id !== $order->restaurant_id) {
            return $this->sendError('Anda tidak memiliki akses.', null, 403);
        }

        $validator = Validator::make($request->all(), [
            'status' => 'required|in:cooking,completed',
            'notes' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return $this->sendError('Validation error.', $validator->errors(), 422);
        }

        $newStatus = $request->status;
        $oldStatus = $order->status;

        // Validate transition
        $validTransitions = [
            'pending' => ['cooking', 'completed'],
            'cooking' => ['completed'],
        ];

        if (!isset($validTransitions[$oldStatus]) || !in_array($newStatus, $validTransitions[$oldStatus])) {
            return $this->sendError("Tidak bisa mengubah status dari '{$oldStatus}' ke '{$newStatus}'.", null, 422);
        }

        DB::beginTransaction();

        try {
            $order->update([
                'status' => $newStatus,
                'completed_at' => $newStatus === 'completed' ? now() : $order->completed_at,
            ]);

            // Log status change
            OrderStatusLog::create([
                'order_id' => $order->id,
                'old_status' => $oldStatus,
                'new_status' => $newStatus,
                'changed_by' => $user->id,
                'changed_at' => now(),
                'notes' => $request->notes ?? "Status diubah oleh dapur.",
            ]);

            // Notify waiter when completed
            if ($newStatus === 'completed' && $order->user_id) {
                Notification::create([
                    'restaurant_id' => $user->restaurant_id,
                    'user_id' => $order->user_id,
                    'type' => 'order_completed',
                    'title' => 'Pesanan Selesai!',
                    'message' => "Order #{$order->order_number} atas nama {$order->customer_name} telah selesai.",
                    'data' => ['order_id' => $order->id],
                    'created_at' => now(),
                ]);
            }

            DB::commit();

            $order->load(['orderItems.menuItem', 'user']);

            return $this->sendResponse($order, 'Status order berhasil diupdate.');
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->sendError('Gagal update status.', $e->getMessage(), 500);
        }
    }

    /**
     * Process payment for completed order (Waiter: completed → paid).
     *
     * PATCH /api/orders/{order}/pay
     * Access: waiter
     *
     * Body:
     * {
     *   "payment_amount": 100000,
     *   "payment_method": "cash"
     * }
     */
    public function processPayment(Request $request, Order $order): JsonResponse
    {
        $user = $request->user();

        if ($user->restaurant_id !== $order->restaurant_id) {
            return $this->sendError('Anda tidak memiliki akses.', null, 403);
        }

        if ($order->status !== 'completed') {
            return $this->sendError('Order harus berstatus completed sebelum pembayaran.', null, 422);
        }

        $validator = Validator::make($request->all(), [
            'payment_amount' => 'required|numeric|min:0',
            'payment_method' => 'required|in:cash,qris,transfer,debit,credit',
        ]);

        if ($validator->fails()) {
            return $this->sendError('Validation error.', $validator->errors(), 422);
        }

        $paymentAmount = (float) $request->payment_amount;
        $totalAmount = (float) $order->total_amount;

        if ($paymentAmount < $totalAmount) {
            return $this->sendError('Jumlah pembayaran kurang dari total pesanan.', null, 422);
        }

        $changeAmount = $paymentAmount - $totalAmount;

        DB::beginTransaction();

        try {
            $order->update([
                'status' => 'paid',
                'payment_amount' => $paymentAmount,
                'change_amount' => $changeAmount,
                'payment_method' => $request->payment_method,
                'paid_at' => now(),
            ]);

            // Log status change
            OrderStatusLog::create([
                'order_id' => $order->id,
                'old_status' => 'completed',
                'new_status' => 'paid',
                'changed_by' => $user->id,
                'changed_at' => now(),
                'notes' => "Pembayaran via {$request->payment_method}.",
            ]);

            DB::commit();

            $order->load(['orderItems.menuItem', 'user']);

            return $this->sendResponse($order, 'Pembayaran berhasil diproses.');
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->sendError('Gagal proses pembayaran.', $e->getMessage(), 500);
        }
    }

    /**
     * Cancel an order.
     *
     * PATCH /api/orders/{order}/cancel
     * Access: owner, admin, manager, waiter (own order only if waiter)
     *
     * Body: { "cancellation_reason": "Customer request" }
     */
    public function cancel(Request $request, Order $order): JsonResponse
    {
        $user = $request->user();

        if ($user->restaurant_id !== $order->restaurant_id) {
            return $this->sendError('Anda tidak memiliki akses.', null, 403);
        }

        // Waiter can only cancel their own orders
        if ($user->isWaiter() && $order->user_id !== $user->id) {
            return $this->sendError('Anda hanya bisa membatalkan pesanan Anda sendiri.', null, 403);
        }

        if (in_array($order->status, ['paid', 'cancelled'])) {
            return $this->sendError("Order dengan status '{$order->status}' tidak bisa dibatalkan.", null, 422);
        }

        $validator = Validator::make($request->all(), [
            'cancellation_reason' => 'required|string',
        ]);

        if ($validator->fails()) {
            return $this->sendError('Validation error.', $validator->errors(), 422);
        }

        DB::beginTransaction();

        try {
            $oldStatus = $order->status;

            $order->update([
                'status' => 'cancelled',
                'cancelled_at' => now(),
                'cancellation_reason' => $request->cancellation_reason,
            ]);

            OrderStatusLog::create([
                'order_id' => $order->id,
                'old_status' => $oldStatus,
                'new_status' => 'cancelled',
                'changed_by' => $user->id,
                'changed_at' => now(),
                'notes' => $request->cancellation_reason,
            ]);

            DB::commit();

            $order->load(['orderItems.menuItem', 'user']);

            return $this->sendResponse($order, 'Order berhasil dibatalkan.');
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->sendError('Gagal membatalkan order.', $e->getMessage(), 500);
        }
    }

    /**
     * Get pending orders for kitchen display.
     *
     * GET /api/orders/kitchen/pending
     * Access: kitchen, owner, admin, manager
     */
    public function kitchenPending(Request $request): JsonResponse
    {
        $user = $request->user();

        $orders = Order::with(['orderItems.menuItem', 'user'])
            ->where('restaurant_id', $user->restaurant_id)
            ->whereIn('status', ['pending', 'cooking'])
            ->orderBy('created_at')
            ->get();

        return $this->sendResponse($orders);
    }

    /**
     * Get completed orders for waiter notification.
     *
     * GET /api/orders/waiter/completed
     * Access: waiter
     */
    public function waiterCompleted(Request $request): JsonResponse
    {
        $user = $request->user();

        $orders = Order::with(['orderItems.menuItem'])
            ->where('restaurant_id', $user->restaurant_id)
            ->where('status', 'completed')
            ->orderBy('completed_at')
            ->get();

        return $this->sendResponse($orders);
    }
}
