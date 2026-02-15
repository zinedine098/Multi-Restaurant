<?php

namespace App\Http\Controllers\Api;

use App\Models\MenuItem;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class MenuItemController extends BaseController
{
    /**
     * Display a listing of menu items.
     *
     * GET /api/menu-items
     * Access: all authenticated users (scoped to restaurant)
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        $query = MenuItem::with('category');

        if ($user->hasRole(['owner', 'admin'])) {
            if ($request->has('restaurant_id')) {
                $query->where('restaurant_id', $request->restaurant_id);
            }
        } else {
            $query->where('restaurant_id', $user->restaurant_id);
        }

        // Optional filters
        if ($request->has('category_id')) {
            $query->where('category_id', $request->category_id);
        }

        if ($request->has('is_available')) {
            $query->where('is_available', filter_var($request->is_available, FILTER_VALIDATE_BOOLEAN));
        }

        if ($request->has('search')) {
            $query->where('name', 'like', '%' . $request->search . '%');
        }

        $menuItems = $query->orderBy('name')->get();

        return $this->sendResponse($menuItems);
    }

    /**
     * Store a newly created menu item.
     *
     * POST /api/menu-items
     * Access: owner, admin, manager
     */
    public function store(Request $request): JsonResponse
    {
        $user = $request->user();

        $validator = Validator::make($request->all(), [
            'restaurant_id' => 'sometimes|required|exists:restaurants,id',
            'category_id' => 'nullable|exists:menu_categories,id',
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'price' => 'required|numeric|min:0',
            'cost_price' => 'nullable|numeric|min:0',
            'image_url' => 'nullable|string|max:500',
            'is_available' => 'nullable|boolean',
            'is_featured' => 'nullable|boolean',
            'preparation_time' => 'nullable|integer|min:0',
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

        $menuItem = MenuItem::create($data);
        $menuItem->load('category');

        return $this->sendResponse($menuItem, 'Menu item berhasil dibuat.', 201);
    }

    /**
     * Display the specified menu item.
     *
     * GET /api/menu-items/{menuItem}
     */
    public function show(Request $request, MenuItem $menuItem): JsonResponse
    {
        $user = $request->user();

        if (!$user->hasRole(['owner', 'admin']) && $user->restaurant_id !== $menuItem->restaurant_id) {
            return $this->sendError('Anda tidak memiliki akses.', null, 403);
        }

        $menuItem->load('category');

        return $this->sendResponse($menuItem);
    }

    /**
     * Update the specified menu item.
     *
     * PUT /api/menu-items/{menuItem}
     * Access: owner, admin, manager
     */
    public function update(Request $request, MenuItem $menuItem): JsonResponse
    {
        $user = $request->user();

        if (!$user->hasRole(['owner', 'admin']) && $user->restaurant_id !== $menuItem->restaurant_id) {
            return $this->sendError('Anda tidak memiliki akses.', null, 403);
        }

        $validator = Validator::make($request->all(), [
            'category_id' => 'nullable|exists:menu_categories,id',
            'name' => 'sometimes|required|string|max:255',
            'description' => 'nullable|string',
            'price' => 'sometimes|required|numeric|min:0',
            'cost_price' => 'nullable|numeric|min:0',
            'image_url' => 'nullable|string|max:500',
            'is_available' => 'nullable|boolean',
            'is_featured' => 'nullable|boolean',
            'preparation_time' => 'nullable|integer|min:0',
        ]);

        if ($validator->fails()) {
            return $this->sendError('Validation error.', $validator->errors(), 422);
        }

        $menuItem->update($validator->validated());
        $menuItem->load('category');

        return $this->sendResponse($menuItem, 'Menu item berhasil diupdate.');
    }

    /**
     * Remove the specified menu item (soft delete).
     *
     * DELETE /api/menu-items/{menuItem}
     * Access: owner, admin, manager
     */
    public function destroy(Request $request, MenuItem $menuItem): JsonResponse
    {
        $user = $request->user();

        if (!$user->hasRole(['owner', 'admin']) && $user->restaurant_id !== $menuItem->restaurant_id) {
            return $this->sendError('Anda tidak memiliki akses.', null, 403);
        }

        $menuItem->delete();

        return $this->sendResponse(null, 'Menu item berhasil dihapus.');
    }
}
