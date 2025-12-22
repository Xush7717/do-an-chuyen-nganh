<?php

use App\Http\Controllers\AdminCategoryController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\CartController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\CheckoutController;
use App\Http\Controllers\CouponController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\ReviewController;
use App\Http\Controllers\Seller\CouponController as SellerCouponController;
use App\Http\Controllers\Seller\OrderController as SellerOrderController;
use App\Http\Controllers\Seller\ProductController as SellerProductController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::prefix('auth')->group(function () {
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/login', [AuthController::class, 'login']);

    Route::middleware('auth:sanctum')->group(function () {
        Route::post('/logout', [AuthController::class, 'logout']);
        Route::get('/me', [AuthController::class, 'me']);
    });
});

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

// Public routes
Route::get('/categories', [CategoryController::class, 'index']);
Route::get('/products', [ProductController::class, 'index']);
Route::get('/products/{id}', [ProductController::class, 'show']);

// Review routes
Route::get('/products/{productId}/reviews', [ReviewController::class, 'index']);
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/reviews', [ReviewController::class, 'store']);
    Route::get('/products/{productId}/can-review', [ReviewController::class, 'canReview']);
});

// Coupon routes
Route::post('/coupons/available', [CouponController::class, 'available']);
Route::post('/coupons/apply', [CouponController::class, 'apply']);

// Cart routes (protected)
Route::middleware('auth:sanctum')->prefix('cart')->group(function () {
    Route::get('/', [CartController::class, 'index']);
    Route::post('/items', [CartController::class, 'store']);
    Route::put('/items/{itemId}', [CartController::class, 'update']);
    Route::delete('/items/{itemId}', [CartController::class, 'destroy']);
});

// Checkout routes (protected)
Route::middleware('auth:sanctum')->prefix('checkout')->group(function () {
    Route::post('/intent', [CheckoutController::class, 'createPaymentIntent']);
    Route::post('/place-order', [CheckoutController::class, 'placeOrder']);
});

// Order routes (protected)
Route::middleware('auth:sanctum')->prefix('orders')->group(function () {
    Route::get('/', [OrderController::class, 'index']);
    Route::get('/{id}', [OrderController::class, 'show']);
});

// Admin routes
Route::prefix('admin')->middleware(['auth:sanctum', 'admin'])->group(function () {
    Route::apiResource('categories', AdminCategoryController::class);
});

// Seller routes
Route::prefix('seller')->middleware(['auth:sanctum', 'seller'])->group(function () {
    Route::get('/stats', [SellerOrderController::class, 'stats']);
    Route::apiResource('products', SellerProductController::class);
    Route::get('/orders', [SellerOrderController::class, 'index']);
    Route::get('/orders/{id}', [SellerOrderController::class, 'show']);
    Route::patch('/orders/{id}/status', [SellerOrderController::class, 'updateStatus']);
    Route::get('/coupons', [SellerCouponController::class, 'index']);
    Route::post('/coupons', [SellerCouponController::class, 'store']);
    Route::delete('/coupons/{id}', [SellerCouponController::class, 'destroy']);
});
