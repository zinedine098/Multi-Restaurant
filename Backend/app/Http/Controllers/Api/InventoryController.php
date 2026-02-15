<?php

namespace App\Http\Controllers\Api;

use App\Models\InventoryItem;
use App\Models\InventoryTransaction;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class InventoryController extends BaseController
{
    // ══════════════════════════════════════════════════════════
    //  INVENTORY ITEMS
    // ══════════════════════════════════════════════════════════

    /**
     * Display a listing of inventory items.
     *
     * GET /api/inventory-items
     * Access: owner, admin (all) | manager (own branch)
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        $query = InventoryItem::query();

        if ($user->hasRole(['owner', 'admin'])) {
            if ($request->has('restaurant_id')) {
                $query->where('restaurant_id', $request->restaurant_id);
            }
        } else {
            $query->where('restaurant_id', $user->restaurant_id);
        }

        if ($request->has('low_stock') && $request->low_stock) {
            $query->whereColumn('current_stock', '<=', 'min_stock');
        }

        $perPage = $request->get('per_page', 15);
        $items = $query->orderBy('name')->paginate($perPage);

        return $this->sendPaginatedResponse($items);
    }

    /**
     * Store a newly created inventory item.
     *
     * POST /api/inventory-items
     * Access: owner, admin, manager
     */
    public function store(Request $request): JsonResponse
    {
        $user = $request->user();

        $validator = Validator::make($request->all(), [
            'restaurant_id' => 'sometimes|required|exists:restaurants,id',
            'name' => 'required|string|max:255',
            'unit' => 'required|string|max:20',
            'min_stock' => 'nullable|numeric|min:0',
            'current_stock' => 'nullable|numeric|min:0',
            'unit_cost' => 'nullable|numeric|min:0',
            'supplier_name' => 'nullable|string|max:255',
            'supplier_phone' => 'nullable|string|max:20',
            'is_active' => 'nullable|boolean',
        ]);

        if ($validator->fails()) {
            return $this->sendError('Validation error.', $validator->errors(), 422);
        }

        $data = $validator->validated();

        if (!$user->hasRole(['owner', 'admin'])) {
            $data['restaurant_id'] = $user->restaurant_id;
        } elseif (empty($data['restaurant_id'])) {
            $data['restaurant_id'] = $user->restaurant_id;
        }

        $item = InventoryItem::create($data);

        return $this->sendResponse($item, 'Inventory item berhasil dibuat.', 201);
    }

    /**
     * Display the specified inventory item.
     *
     * GET /api/inventory-items/{inventoryItem}
     */
    public function show(Request $request, InventoryItem $inventoryItem): JsonResponse
    {
        $user = $request->user();

        if (!$user->hasRole(['owner', 'admin']) && $user->restaurant_id !== $inventoryItem->restaurant_id) {
            return $this->sendError('Anda tidak memiliki akses.', null, 403);
        }

        $inventoryItem->load('transactions');

        return $this->sendResponse($inventoryItem);
    }

    /**
     * Update the specified inventory item.
     *
     * PUT /api/inventory-items/{inventoryItem}
     * Access: owner, admin, manager
     */
    public function update(Request $request, InventoryItem $inventoryItem): JsonResponse
    {
        $user = $request->user();

        if (!$user->hasRole(['owner', 'admin']) && $user->restaurant_id !== $inventoryItem->restaurant_id) {
            return $this->sendError('Anda tidak memiliki akses.', null, 403);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|required|string|max:255',
            'unit' => 'sometimes|required|string|max:20',
            'min_stock' => 'nullable|numeric|min:0',
            'unit_cost' => 'nullable|numeric|min:0',
            'supplier_name' => 'nullable|string|max:255',
            'supplier_phone' => 'nullable|string|max:20',
            'is_active' => 'nullable|boolean',
        ]);

        if ($validator->fails()) {
            return $this->sendError('Validation error.', $validator->errors(), 422);
        }

        $inventoryItem->update($validator->validated());

        return $this->sendResponse($inventoryItem, 'Inventory item berhasil diupdate.');
    }

    /**
     * Remove the specified inventory item.
     *
     * DELETE /api/inventory-items/{inventoryItem}
     * Access: owner, admin, manager
     */
    public function destroy(Request $request, InventoryItem $inventoryItem): JsonResponse
    {
        $user = $request->user();

        if (!$user->hasRole(['owner', 'admin']) && $user->restaurant_id !== $inventoryItem->restaurant_id) {
            return $this->sendError('Anda tidak memiliki akses.', null, 403);
        }

        $inventoryItem->delete();

        return $this->sendResponse(null, 'Inventory item berhasil dihapus.');
    }

    // ══════════════════════════════════════════════════════════
    //  INVENTORY TRANSACTIONS
    // ══════════════════════════════════════════════════════════

    /**
     * Display transactions for an inventory item.
     *
     * GET /api/inventory-items/{inventoryItem}/transactions
     */
    public function transactions(Request $request, InventoryItem $inventoryItem): JsonResponse
    {
        $user = $request->user();

        if (!$user->hasRole(['owner', 'admin']) && $user->restaurant_id !== $inventoryItem->restaurant_id) {
            return $this->sendError('Anda tidak memiliki akses.', null, 403);
        }

        $perPage = $request->get('per_page', 15);
        $transactions = $inventoryItem->transactions()
            ->with('createdByUser')
            ->orderByDesc('created_at')
            ->paginate($perPage);

        return $this->sendPaginatedResponse($transactions);
    }

    /**
     * Create a new inventory transaction (stock in/out/adjustment).
     *
     * POST /api/inventory-items/{inventoryItem}/transactions
     * Access: owner, admin, manager
     *
     * Body:
     * {
     *   "type": "in",
     *   "quantity": 10,
     *   "unit_cost": 5000,
     *   "notes": "Purchase from supplier"
     * }
     */
    public function storeTransaction(Request $request, InventoryItem $inventoryItem): JsonResponse
    {
        $user = $request->user();

        if (!$user->hasRole(['owner', 'admin']) && $user->restaurant_id !== $inventoryItem->restaurant_id) {
            return $this->sendError('Anda tidak memiliki akses.', null, 403);
        }

        $validator = Validator::make($request->all(), [
            'type' => 'required|in:in,out,adjustment',
            'quantity' => 'required|numeric|min:0.01',
            'unit_cost' => 'nullable|numeric|min:0',
            'reference_type' => 'nullable|string|max:50',
            'reference_id' => 'nullable|integer',
            'notes' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return $this->sendError('Validation error.', $validator->errors(), 422);
        }

        DB::beginTransaction();

        try {
            $unitCost = $request->unit_cost ?? $inventoryItem->unit_cost;
            $quantity = (float) $request->quantity;
            $totalCost = $unitCost * $quantity;

            $transaction = InventoryTransaction::create([
                'restaurant_id' => $inventoryItem->restaurant_id,
                'inventory_item_id' => $inventoryItem->id,
                'type' => $request->type,
                'quantity' => $quantity,
                'unit_cost' => $unitCost,
                'total_cost' => $totalCost,
                'reference_type' => $request->reference_type,
                'reference_id' => $request->reference_id,
                'notes' => $request->notes,
                'created_by' => $user->id,
                'created_at' => now(),
            ]);

            // Update current stock
            if ($request->type === 'in') {
                $inventoryItem->increment('current_stock', $quantity);
            } elseif ($request->type === 'out') {
                if ($inventoryItem->current_stock < $quantity) {
                    DB::rollBack();
                    return $this->sendError('Stok tidak mencukupi.', null, 422);
                }
                $inventoryItem->decrement('current_stock', $quantity);
            } else {
                // adjustment: set to given quantity
                $inventoryItem->update(['current_stock' => $quantity]);
            }

            DB::commit();

            $transaction->load('createdByUser');

            return $this->sendResponse($transaction, 'Transaksi inventory berhasil.', 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->sendError('Gagal membuat transaksi.', $e->getMessage(), 500);
        }
    }
}
