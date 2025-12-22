<?php

namespace App\Http\Controllers;

use App\Models\Cart;
use App\Models\Coupon;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Payment;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Stripe\Exception\ApiErrorException;
use Stripe\PaymentIntent;
use Stripe\Stripe;

class CheckoutController extends Controller
{
    public function __construct()
    {
        // Set Stripe API key
        Stripe::setApiKey(config('services.stripe.secret'));
    }

    /**
     * Create a Stripe PaymentIntent for the user's cart
     */
    public function createPaymentIntent(Request $request): JsonResponse
    {
        try {
            $user = $request->user();

            // Get user's cart
            $cart = Cart::where('user_id', $user->id)
                ->with(['cartItems.product'])
                ->first();

            if (! $cart || $cart->cartItems->isEmpty()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Your cart is empty',
                ], 400);
            }

            // Calculate subtotal from cart items
            $subtotal = 0;
            foreach ($cart->cartItems as $cartItem) {
                $subtotal += $cartItem->product->price * $cartItem->quantity;
            }

            // Handle multiple coupons (one per seller)
            $couponCodes = $request->input('coupon_codes', []);
            $totalDiscount = 0;
            $couponDetails = [];
            $appliedSellerIds = [];

            if (! empty($couponCodes)) {
                // Get products for seller mapping
                $products = \App\Models\Product::whereIn('id', $cart->cartItems->pluck('product_id'))->get()->keyBy('id');

                foreach ($couponCodes as $couponCode) {
                    $coupon = Coupon::where('code', strtoupper($couponCode))->first();

                    if (! $coupon) {
                        return response()->json([
                            'success' => false,
                            'message' => "Invalid coupon code: {$couponCode}",
                        ], 400);
                    }

                    // Validate coupon
                    if ($coupon->expires_at && $coupon->expires_at->isPast()) {
                        return response()->json([
                            'success' => false,
                            'message' => "Coupon {$couponCode} has expired",
                        ], 400);
                    }

                    if ($coupon->usage_limit && $coupon->usage_count >= $coupon->usage_limit) {
                        return response()->json([
                            'success' => false,
                            'message' => "Coupon {$couponCode} has reached its usage limit",
                        ], 400);
                    }

                    // Check for duplicate seller coupons
                    if (in_array($coupon->seller_id, $appliedSellerIds)) {
                        return response()->json([
                            'success' => false,
                            'message' => 'Only one coupon per seller is allowed',
                        ], 400);
                    }

                    // Calculate seller subtotal
                    $sellerSubtotal = 0;
                    foreach ($cart->cartItems as $cartItem) {
                        $product = $products->get($cartItem->product_id);
                        if ($product && $product->seller_id == $coupon->seller_id) {
                            $sellerSubtotal += $product->price * $cartItem->quantity;
                        }
                    }

                    if ($sellerSubtotal == 0) {
                        return response()->json([
                            'success' => false,
                            'message' => "Coupon {$couponCode} does not apply to any items in your cart",
                        ], 400);
                    }

                    if ($sellerSubtotal < $coupon->min_order_value) {
                        return response()->json([
                            'success' => false,
                            'message' => "Coupon {$couponCode} requires minimum order value of $".$coupon->min_order_value,
                        ], 400);
                    }

                    // Calculate discount for this seller
                    $discountAmount = 0;
                    if ($coupon->type === 'percentage') {
                        $discountAmount = ($sellerSubtotal * $coupon->value) / 100;
                    } else {
                        $discountAmount = min($coupon->value, $sellerSubtotal);
                    }

                    $totalDiscount += $discountAmount;
                    $appliedSellerIds[] = $coupon->seller_id;
                    $couponDetails[] = [
                        'id' => $coupon->id,
                        'code' => $coupon->code,
                        'seller_id' => $coupon->seller_id,
                        'discount' => $discountAmount,
                    ];
                }
            }

            // Calculate final amount with tax
            $taxRate = 0.10; // 10% tax rate
            $amountAfterDiscount = $subtotal - $totalDiscount;
            $taxAmount = $amountAfterDiscount * $taxRate;
            $finalAmount = $amountAfterDiscount + $taxAmount;

            // Stripe requires amount in cents (smallest currency unit)
            $amountInCents = (int) ($finalAmount * 100);

            // Create PaymentIntent
            $paymentIntent = PaymentIntent::create([
                'amount' => $amountInCents,
                'currency' => 'usd',
                'metadata' => [
                    'user_id' => $user->id,
                    'cart_id' => $cart->id,
                    'discount_amount' => $totalDiscount,
                    'tax_amount' => $taxAmount,
                    'coupons' => json_encode($couponDetails),
                ],
                'automatic_payment_methods' => [
                    'enabled' => true,
                ],
            ]);

            return response()->json([
                'success' => true,
                'data' => [
                    'clientSecret' => $paymentIntent->client_secret,
                    'amount' => $finalAmount,
                ],
            ]);
        } catch (ApiErrorException $e) {
            Log::error('Stripe PaymentIntent creation failed: '.$e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to create payment intent: '.$e->getMessage(),
            ], 500);
        } catch (\Exception $e) {
            Log::error('Checkout error: '.$e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'An error occurred during checkout',
            ], 500);
        }
    }

    /**
     * Place order after successful payment
     */
    public function placeOrder(Request $request): JsonResponse
    {
        try {
            // Validate request
            $validated = $request->validate([
                'payment_intent_id' => 'required|string',
                'coupon_codes' => 'nullable|array',
                'coupon_codes.*' => 'string',
                'shipping_address' => 'required|array',
                'shipping_address.name' => 'required|string|max:255',
                'shipping_address.phone' => 'required|string|max:20',
                'shipping_address.address' => 'required|string',
                'shipping_address.city' => 'required|string|max:100',
            ]);

            Log::info('Place order request received', [
                'user_id' => $request->user()->id,
                'payment_intent_id' => $validated['payment_intent_id'],
            ]);

            $user = $request->user();

            // Verify PaymentIntent with Stripe
            try {
                $paymentIntent = PaymentIntent::retrieve($validated['payment_intent_id']);

                if ($paymentIntent->status !== 'succeeded') {
                    Log::warning('Payment not successful', [
                        'payment_intent_id' => $validated['payment_intent_id'],
                        'status' => $paymentIntent->status,
                    ]);

                    return response()->json([
                        'success' => false,
                        'message' => 'Payment was not successful',
                    ], 400);
                }
            } catch (ApiErrorException $e) {
                Log::error('Stripe API error during verification', [
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]);

                return response()->json([
                    'success' => false,
                    'message' => 'Failed to verify payment',
                ], 500);
            }

            // Get user's cart
            $cart = Cart::where('user_id', $user->id)
                ->with(['cartItems.product'])
                ->first();

            if (! $cart || $cart->cartItems->isEmpty()) {
                Log::warning('Empty cart during checkout', ['user_id' => $user->id]);

                return response()->json([
                    'success' => false,
                    'message' => 'Your cart is empty',
                ], 400);
            }

            // Start database transaction
            DB::beginTransaction();

            try {
                // Calculate subtotal
                $subtotal = 0;
                foreach ($cart->cartItems as $cartItem) {
                    if (! $cartItem->product) {
                        throw new \Exception("Product not found for cart item ID: {$cartItem->id}");
                    }
                    $subtotal += $cartItem->product->price * $cartItem->quantity;
                }

                // Get coupon data from PaymentIntent metadata
                $discountAmount = $paymentIntent->metadata->discount_amount ?? 0;
                $taxAmount = $paymentIntent->metadata->tax_amount ?? 0;
                $couponsData = json_decode($paymentIntent->metadata->coupons ?? '[]', true);
                $finalAmount = $subtotal - $discountAmount + $taxAmount;

                // Increment usage count for all applied coupons
                foreach ($couponsData as $couponData) {
                    $coupon = Coupon::find($couponData['id']);
                    if ($coupon) {
                        $coupon->increment('usage_count');
                        Log::info('Coupon usage incremented', [
                            'coupon_id' => $coupon->id,
                            'code' => $coupon->code,
                            'new_usage_count' => $coupon->fresh()->usage_count,
                        ]);
                    }
                }

                // Format shipping address as JSON string
                $shippingAddress = json_encode($validated['shipping_address'], JSON_UNESCAPED_UNICODE);

                if ($shippingAddress === false) {
                    throw new \Exception('Failed to encode shipping address');
                }

                Log::info('Creating order', [
                    'user_id' => $user->id,
                    'subtotal' => $subtotal,
                    'discount_amount' => $discountAmount,
                    'final_amount' => $finalAmount,
                    'cart_items_count' => $cart->cartItems->count(),
                ]);

                // Create Order (store first coupon ID for backward compatibility)
                $firstCouponId = ! empty($couponsData) ? $couponsData[0]['id'] : null;

                $order = Order::create([
                    'user_id' => $user->id,
                    'coupon_id' => $firstCouponId,
                    'status' => 'processing',
                    'total_amount' => $subtotal,
                    'discount_amount' => $discountAmount,
                    'tax_amount' => $taxAmount,
                    'final_amount' => $finalAmount,
                    'shipping_address' => $shippingAddress,
                ]);

                // Move cart items to order items and decrement stock
                foreach ($cart->cartItems as $cartItem) {
                    $product = $cartItem->product;

                    // Safety check: Ensure sufficient stock
                    if ($product->stock_quantity < $cartItem->quantity) {
                        throw new \Exception(
                            "Insufficient stock for product '{$product->name}'. Available: {$product->stock_quantity}, Requested: {$cartItem->quantity}"
                        );
                    }

                    // Decrement product stock
                    $product->decrement('stock_quantity', $cartItem->quantity);

                    Log::info('Stock decremented', [
                        'product_id' => $product->id,
                        'product_name' => $product->name,
                        'quantity_sold' => $cartItem->quantity,
                        'remaining_stock' => $product->fresh()->stock_quantity,
                    ]);

                    OrderItem::create([
                        'order_id' => $order->id,
                        'product_id' => $cartItem->product_id,
                        'product_name' => $product->name,
                        'quantity' => $cartItem->quantity,
                        'price_at_purchase' => $product->price,
                    ]);
                }

                // Create Payment record
                Payment::create([
                    'order_id' => $order->id,
                    'amount' => $finalAmount,
                    'gateway' => 'stripe',
                    'transaction_id' => $validated['payment_intent_id'],
                    'status' => 'succeeded',
                ]);

                // Clear cart items
                $cart->cartItems()->delete();

                // Commit transaction
                DB::commit();

                Log::info('Order placed successfully', [
                    'order_id' => $order->id,
                    'user_id' => $user->id,
                ]);

                return response()->json([
                    'success' => true,
                    'message' => 'Order placed successfully',
                    'data' => [
                        'order_id' => $order->id,
                        'order_number' => $order->id,
                        'total_amount' => $subtotal,
                        'discount_amount' => $discountAmount,
                        'final_amount' => $finalAmount,
                    ],
                ], 201);
            } catch (\Exception $e) {
                DB::rollBack();

                Log::error('Database transaction failed', [
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                    'user_id' => $user->id,
                ]);

                throw $e;
            }
        } catch (\Illuminate\Validation\ValidationException $e) {
            Log::error('Validation failed in placeOrder', [
                'errors' => $e->errors(),
                'input' => $request->all(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            Log::error('Order placement error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'An error occurred while placing your order. Please contact support.',
            ], 500);
        }
    }
}
