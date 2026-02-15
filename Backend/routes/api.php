<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\InventoryController;
use App\Http\Controllers\Api\MenuCategoryController;
use App\Http\Controllers\Api\MenuItemController;
use App\Http\Controllers\Api\NotificationController;
use App\Http\Controllers\Api\OrderController;
use App\Http\Controllers\Api\ReportController;
use App\Http\Controllers\Api\RestaurantController;
use App\Http\Controllers\Api\SettingController;
use App\Http\Controllers\Api\UserController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Authentication Routes
|--------------------------------------------------------------------------
*/

Route::prefix('auth')->group(function () {
    Route::post('/login', [AuthController::class, 'login']);

    Route::middleware('auth:sanctum')->group(function () {
        Route::post('/logout', [AuthController::class, 'logout']);
        Route::get('/me', [AuthController::class, 'me']);
    });
});

/*
|--------------------------------------------------------------------------
| Protected API Routes (auth:sanctum)
|--------------------------------------------------------------------------
*/
Route::middleware('auth:sanctum')->group(function () {

    /*
    |----------------------------------------------------------------------
    | Restaurants - owner/admin: CRUD, others: view own branch
    |----------------------------------------------------------------------
    */
    Route::middleware('role:owner,admin')->group(function () {
        Route::post('/restaurants', [RestaurantController::class, 'store']);
        Route::put('/restaurants/{restaurant}', [RestaurantController::class, 'update']);
    });
    Route::middleware('role:owner')->group(function () {
        Route::delete('/restaurants/{restaurant}', [RestaurantController::class, 'destroy']);
    });
    Route::get('/restaurants', [RestaurantController::class, 'index']);
    Route::get('/restaurants/{restaurant}', [RestaurantController::class, 'show']);

    /*
    |----------------------------------------------------------------------
    | Users - owner/admin: CRUD, manager: view own branch
    |----------------------------------------------------------------------
    */
    Route::middleware('role:owner,admin')->group(function () {
        Route::post('/users', [UserController::class, 'store']);
        Route::put('/users/{user}', [UserController::class, 'update']);
    });
    Route::middleware('role:owner')->group(function () {
        Route::delete('/users/{user}', [UserController::class, 'destroy']);
    });
    Route::middleware('role:owner,admin,manager')->group(function () {
        Route::get('/users', [UserController::class, 'index']);
        Route::get('/users/{user}', [UserController::class, 'show']);
    });

    /*
    |----------------------------------------------------------------------
    | Menu Categories - owner/admin/manager: CRUD, all: view
    |----------------------------------------------------------------------
    */
    Route::get('/menu-categories', [MenuCategoryController::class, 'index']);
    Route::get('/menu-categories/{menuCategory}', [MenuCategoryController::class, 'show']);
    Route::middleware('role:owner,admin,manager')->group(function () {
        Route::post('/menu-categories', [MenuCategoryController::class, 'store']);
        Route::put('/menu-categories/{menuCategory}', [MenuCategoryController::class, 'update']);
        Route::delete('/menu-categories/{menuCategory}', [MenuCategoryController::class, 'destroy']);
    });

    /*
    |----------------------------------------------------------------------
    | Menu Items - owner/admin/manager: CRUD, all: view
    |----------------------------------------------------------------------
    */
    Route::get('/menu-items', [MenuItemController::class, 'index']);
    Route::get('/menu-items/{menuItem}', [MenuItemController::class, 'show']);
    Route::middleware('role:owner,admin,manager')->group(function () {
        Route::post('/menu-items', [MenuItemController::class, 'store']);
        Route::put('/menu-items/{menuItem}', [MenuItemController::class, 'update']);
        Route::delete('/menu-items/{menuItem}', [MenuItemController::class, 'destroy']);
    });

    /*
    |----------------------------------------------------------------------
    | Orders
    |----------------------------------------------------------------------
    */
    Route::get('/orders', [OrderController::class, 'index']);
    Route::get('/orders/{order}', [OrderController::class, 'show']);

    // Waiter: create order
    Route::middleware('role:waiter')->group(function () {
        Route::post('/orders', [OrderController::class, 'store']);
        Route::patch('/orders/{order}/pay', [OrderController::class, 'processPayment']);
        Route::get('/orders-waiter/completed', [OrderController::class, 'waiterCompleted']);
    });

    // Kitchen: update status
    Route::middleware('role:kitchen')->group(function () {
        Route::patch('/orders/{order}/status', [OrderController::class, 'updateStatus']);
    });

    // Kitchen display
    Route::middleware('role:owner,admin,manager,kitchen')->group(function () {
        Route::get('/orders-kitchen/pending', [OrderController::class, 'kitchenPending']);
    });

    // Cancel order
    Route::middleware('role:owner,admin,manager,waiter')->group(function () {
        Route::patch('/orders/{order}/cancel', [OrderController::class, 'cancel']);
    });

    /*
    |----------------------------------------------------------------------
    | Inventory - owner/admin/manager
    |----------------------------------------------------------------------
    */
    Route::middleware('role:owner,admin,manager')->group(function () {
        Route::apiResource('inventory-items', InventoryController::class);
        Route::get('/inventory-items/{inventoryItem}/transactions', [InventoryController::class, 'transactions']);
        Route::post('/inventory-items/{inventoryItem}/transactions', [InventoryController::class, 'storeTransaction']);
    });

    /*
    |----------------------------------------------------------------------
    | Reports - owner/admin: all, manager: own branch
    |----------------------------------------------------------------------
    */
    Route::middleware('role:owner,admin,manager')->group(function () {
        Route::get('/reports/dashboard', [ReportController::class, 'dashboard']);
        Route::get('/reports/daily-sales', [ReportController::class, 'dailySales']);
        Route::get('/reports/popular-items', [ReportController::class, 'popularItems']);
    });

    /*
    |----------------------------------------------------------------------
    | Settings - owner: system, admin/manager: branch
    |----------------------------------------------------------------------
    */
    Route::middleware('role:owner,admin,manager')->group(function () {
        Route::get('/settings', [SettingController::class, 'index']);
        Route::post('/settings', [SettingController::class, 'upsert']);
    });
    Route::middleware('role:owner')->group(function () {
        Route::delete('/settings/{setting}', [SettingController::class, 'destroy']);
    });

    /*
    |----------------------------------------------------------------------
    | Notifications
    |----------------------------------------------------------------------
    */
    Route::get('/notifications', [NotificationController::class, 'index']);
    Route::get('/notifications/unread-count', [NotificationController::class, 'unreadCount']);
    Route::patch('/notifications/{notification}/read', [NotificationController::class, 'markAsRead']);
    Route::patch('/notifications/read-all', [NotificationController::class, 'markAllAsRead']);
});
