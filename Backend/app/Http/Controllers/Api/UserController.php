<?php

namespace App\Http\Controllers\Api;

use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class UserController extends BaseController
{
    /**
     * Display a listing of users.
     *
     * GET /api/users
     * Access: owner, admin (all) | manager (own branch only)
     */
    public function index(Request $request): JsonResponse
    {
        $authUser = $request->user();
        $query = User::with('restaurant');

        if ($authUser->hasRole(['owner', 'admin'])) {
            // Filter by restaurant_id if provided
            if ($request->has('restaurant_id')) {
                $query->where('restaurant_id', $request->restaurant_id);
            }
        } else {
            // Manager can only see users in their own branch
            $query->where('restaurant_id', $authUser->restaurant_id);
        }

        if ($request->has('role')) {
            $query->where('role', $request->role);
        }

        $users = $query->orderBy('full_name')->get();

        return $this->sendResponse($users);
    }

    /**
     * Store a newly created user.
     *
     * POST /api/users
     * Access: owner, admin
     */
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'restaurant_id' => 'required|exists:restaurants,id',
            'username' => 'required|string|max:50|unique:users,username',
            'password' => 'required|string|min:6',
            'full_name' => 'required|string|max:255',
            'email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:20',
            'role' => 'required|in:owner,admin,manager,waiter,kitchen',
            'avatar_url' => 'nullable|string|max:500',
            'is_active' => 'nullable|boolean',
        ]);

        if ($validator->fails()) {
            return $this->sendError('Validation error.', $validator->errors(), 422);
        }

        $data = $validator->validated();
        $user = User::create($data);
        $user->load('restaurant');

        return $this->sendResponse($user, 'User berhasil dibuat.', 201);
    }

    /**
     * Display the specified user.
     *
     * GET /api/users/{user}
     */
    public function show(Request $request, User $user): JsonResponse
    {
        $authUser = $request->user();

        if (!$authUser->hasRole(['owner', 'admin']) && $authUser->restaurant_id !== $user->restaurant_id) {
            return $this->sendError('Anda tidak memiliki akses.', null, 403);
        }

        $user->load('restaurant');

        return $this->sendResponse($user);
    }

    /**
     * Update the specified user.
     *
     * PUT /api/users/{user}
     * Access: owner, admin
     */
    public function update(Request $request, User $user): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'restaurant_id' => 'sometimes|required|exists:restaurants,id',
            'username' => 'sometimes|required|string|max:50|unique:users,username,' . $user->id,
            'password' => 'nullable|string|min:6',
            'full_name' => 'sometimes|required|string|max:255',
            'email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:20',
            'role' => 'sometimes|required|in:owner,admin,manager,waiter,kitchen',
            'avatar_url' => 'nullable|string|max:500',
            'is_active' => 'nullable|boolean',
        ]);

        if ($validator->fails()) {
            return $this->sendError('Validation error.', $validator->errors(), 422);
        }

        $data = $validator->validated();

        // Remove password if not provided
        if (empty($data['password'])) {
            unset($data['password']);
        }

        $user->update($data);
        $user->load('restaurant');

        return $this->sendResponse($user, 'User berhasil diupdate.');
    }

    /**
     * Remove the specified user (soft delete).
     *
     * DELETE /api/users/{user}
     * Access: owner only
     */
    public function destroy(User $user): JsonResponse
    {
        $user->delete();

        return $this->sendResponse(null, 'User berhasil dihapus.');
    }
}
