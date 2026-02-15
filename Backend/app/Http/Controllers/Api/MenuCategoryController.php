<?php

namespace App\Http\Controllers\Api;

use App\Models\MenuCategory;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class MenuCategoryController extends BaseController
{
    /**
     * Display a listing of menu categories.
     *
     * GET /api/menu-categories
     * Access: all authenticated users (scoped to restaurant)
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        $query = MenuCategory::withCount('menuItems');

        if ($user->hasRole(['owner', 'admin'])) {
            if ($request->has('restaurant_id')) {
                $query->where('restaurant_id', $request->restaurant_id);
            }
        } else {
            $query->where('restaurant_id', $user->restaurant_id);
        }

        $perPage = $request->get('per_page', 15);
        $categories = $query->orderBy('sort_order')->orderBy('name')->paginate($perPage);

        return $this->sendPaginatedResponse($categories);
    }

    /**
     * Store a newly created menu category.
     *
     * POST /api/menu-categories
     * Access: owner, admin, manager
     */
    public function store(Request $request): JsonResponse
    {
        $user = $request->user();

        $validator = Validator::make($request->all(), [
            'restaurant_id' => 'sometimes|required|exists:restaurants,id',
            'name' => 'required|string|max:100',
            'description' => 'nullable|string',
            'sort_order' => 'nullable|integer',
            'is_active' => 'nullable|boolean',
        ]);

        if ($validator->fails()) {
            return $this->sendError('Validation error.', $validator->errors(), 422);
        }

        $data = $validator->validated();

        // Manager can only add to their own restaurant
        if (!$user->hasRole(['owner', 'admin'])) {
            $data['restaurant_id'] = $user->restaurant_id;
        } elseif (empty($data['restaurant_id'])) {
            $data['restaurant_id'] = $user->restaurant_id;
        }

        $category = MenuCategory::create($data);

        return $this->sendResponse($category, 'Kategori menu berhasil dibuat.', 201);
    }

    /**
     * Display the specified menu category.
     *
     * GET /api/menu-categories/{menuCategory}
     */
    public function show(Request $request, MenuCategory $menuCategory): JsonResponse
    {
        $user = $request->user();

        if (!$user->hasRole(['owner', 'admin']) && $user->restaurant_id !== $menuCategory->restaurant_id) {
            return $this->sendError('Anda tidak memiliki akses.', null, 403);
        }

        $menuCategory->load('menuItems');

        return $this->sendResponse($menuCategory);
    }

    /**
     * Update the specified menu category.
     *
     * PUT /api/menu-categories/{menuCategory}
     * Access: owner, admin, manager
     */
    public function update(Request $request, MenuCategory $menuCategory): JsonResponse
    {
        $user = $request->user();

        if (!$user->hasRole(['owner', 'admin']) && $user->restaurant_id !== $menuCategory->restaurant_id) {
            return $this->sendError('Anda tidak memiliki akses.', null, 403);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|required|string|max:100',
            'description' => 'nullable|string',
            'sort_order' => 'nullable|integer',
            'is_active' => 'nullable|boolean',
        ]);

        if ($validator->fails()) {
            return $this->sendError('Validation error.', $validator->errors(), 422);
        }

        $menuCategory->update($validator->validated());

        return $this->sendResponse($menuCategory, 'Kategori menu berhasil diupdate.');
    }

    /**
     * Remove the specified menu category (soft delete).
     *
     * DELETE /api/menu-categories/{menuCategory}
     * Access: owner, admin, manager
     */
    public function destroy(Request $request, MenuCategory $menuCategory): JsonResponse
    {
        $user = $request->user();

        if (!$user->hasRole(['owner', 'admin']) && $user->restaurant_id !== $menuCategory->restaurant_id) {
            return $this->sendError('Anda tidak memiliki akses.', null, 403);
        }

        $menuCategory->delete();

        return $this->sendResponse(null, 'Kategori menu berhasil dihapus.');
    }
}
