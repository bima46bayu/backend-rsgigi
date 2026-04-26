<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\LocationController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\SupplierController;
use App\Http\Controllers\ItemController;
use App\Http\Controllers\TreatmentController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\RecordController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\PurchaseController;
use App\Http\Controllers\GoodsReceiptController;

/*
|--------------------------------------------------------------------------
| Public Routes
|--------------------------------------------------------------------------
*/

Route::post('/login', [AuthController::class, 'login']);

/*
|--------------------------------------------------------------------------
| Protected Routes (Sanctum)
|--------------------------------------------------------------------------
*/

Route::middleware('auth:sanctum')->group(function () {

    Route::post('/logout', [AuthController::class, 'logout']);

    Route::get('/me', function (Request $request) {
        return $request->user()->load('roles', 'location');
    });

    Route::put('/profile', [UserController::class, 'updateProfile']);
    
    Route::apiResource('categories', CategoryController::class);
    Route::apiResource('suppliers', SupplierController::class);
    Route::apiResource('items', ItemController::class);
    Route::apiResource('treatments', TreatmentController::class);
    Route::apiResource('records', RecordController::class)
        ->only(['index','store']);
    Route::apiResource('purchases', PurchaseController::class);

    Route::get('/items/{id}/stocks', [ItemController::class, 'stocks']);
    Route::get('/items/{id}/transactions', [ItemController::class, 'transactions']);

    Route::post('purchases/{purchase}/approve', [PurchaseController::class, 'approve']);
    Route::post('purchases/{purchase}/cancel', [PurchaseController::class, 'cancel']);

    Route::post('records/{id}/items', [RecordController::class, 'updateItems']);
    Route::post('records/{id}/complete', [RecordController::class, 'complete']);
    Route::get('records/{id}', [RecordController::class, 'show']);
    Route::post('records/{id}/reject', [RecordController::class, 'reject']);

    Route::post('items/{id}/stock-in', [ItemController::class, 'stockIn']);
    Route::post('items/{id}/stock-out', [ItemController::class, 'stockOut']);
    Route::get('items/{id}/total-stock', [ItemController::class, 'totalStock']);

    Route::get('/notifications', [NotificationController::class,'index']);
    Route::post('/notifications/{id}/read', [NotificationController::class,'markAsRead']);
    Route::post('/notifications/read-all', [NotificationController::class,'markAllAsRead']);

    Route::get('goods-receipts', [GoodsReceiptController::class, 'index']);
    Route::get('goods-receipts/{goodsReceipt}', [GoodsReceiptController::class, 'show']);
    Route::post('purchases/{purchase}/goods-receipts', 
        [GoodsReceiptController::class, 'store']);
    Route::post('goods-receipts/{goodsReceipt}/complete', 
        [GoodsReceiptController::class, 'complete']);

    /*
    |--------------------------------------------------------------------------
    | Super Admin Only
    |--------------------------------------------------------------------------
    */

    Route::middleware('role:super-admin')->group(function () {
        Route::apiResource('locations', LocationController::class);
        Route::apiResource('users', UserController::class);
    });

});