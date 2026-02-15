<?php

namespace App\Http\Controllers\Api;

use App\Models\Restaurant;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class RestaurantController extends BaseController
{
    /**
     * Display a listing of restaurants.
     *
     * GET /api/restaurants
     * Access: owner, admin (all) | manager, waiter, kitchen (own branch only)
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        $perPage = $request->get('per_page', 15);

        $query = Restaurant::withCount('users', 'orders')->orderBy('name');

        if (!$user->hasRole(['owner', 'admin'])) {
            $query->where('id', $user->restaurant_id);
        }

        $restaurants = $query->paginate($perPage);

        return $this->sendPaginatedResponse($restaurants);
    }

    /**
     * Store a newly created restaurant.
     *
     * POST /api/restaurants
     * Access: owner, admin
     */
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'address' => 'required|string',
            'phone' => 'required|string|max:20',
            'email' => 'nullable|email|max:255',
            'tax_id' => 'nullable|string|max:50',
            'logo_url' => 'nullable|string|max:500',
            'is_active' => 'nullable|boolean',
        ]);

        if ($validator->fails()) {
            return $this->sendError('Validation error.', $validator->errors(), 422);
        }

        $restaurant = Restaurant::create($validator->validated());

        return $this->sendResponse($restaurant, 'Restaurant berhasil dibuat.', 201);
    }

    /**
     * Display the specified restaurant.
     *
     * GET /api/restaurants/{restaurant}
     */
    public function show(Request $request, Restaurant $restaurant): JsonResponse
    {
        $user = $request->user();

        // Non-owner/admin can only view their own branch
        if (!$user->hasRole(['owner', 'admin']) && $user->restaurant_id !== $restaurant->id) {
            return $this->sendError('Anda tidak memiliki akses.', null, 403);
        }

        $restaurant->loadCount('users', 'orders', 'menuItems', 'menuCategories');

        return $this->sendResponse($restaurant);
    }

    /**
     * Update the specified restaurant.
     *
     * PUT /api/restaurants/{restaurant}
     * Access: owner, admin
     */
    public function update(Request $request, Restaurant $restaurant): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|required|string|max:255',
            'address' => 'sometimes|required|string',
            'phone' => 'sometimes|required|string|max:20',
            'email' => 'nullable|email|max:255',
            'tax_id' => 'nullable|string|max:50',
            'logo_url' => 'nullable|string|max:500',
            'is_active' => 'nullable|boolean',
        ]);

        if ($validator->fails()) {
            return $this->sendError('Validation error.', $validator->errors(), 422);
        }

        $restaurant->update($validator->validated());

        return $this->sendResponse($restaurant, 'Restaurant berhasil diupdate.');
    }

    /**
     * Remove the specified restaurant (soft delete).
     *
     * DELETE /api/restaurants/{restaurant}
     * Access: owner only
     */
    public function destroy(Restaurant $restaurant): JsonResponse
    {
        $restaurant->delete();

        return $this->sendResponse(null, 'Restaurant berhasil dihapus.');
    }
}
