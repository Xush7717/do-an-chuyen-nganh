<?php

namespace App\Http\Controllers;

use App\Models\Cart;
use App\Models\CartItem;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CartController extends Controller
{
    /**
     * Get current user's cart with items
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        // Get or create cart for user
        $cart = Cart::firstOrCreate(['user_id' => $user->id]);

        // Load cart items with product details
        $cartItems = $cart->cartItems()
            ->with(['product.category', 'product.seller'])
            ->get()
            ->map(function ($item) {
                return [
                    'id' => $item->id,
                    'product_id' => $item->product_id,
                    'quantity' => $item->quantity,
                    'product' => [
                        'id' => $item->product->id,
                        'name' => $item->product->name,
                        'price' => $item->product->price,
                        'image_url' => $item->product->image_url,
                        'category' => $item->product->category->name ?? null,
                        'seller' => $item->product->seller->name ?? null,
                    ],
                ];
            });

        return response()->json([
            'success' => true,
            'data' => $cartItems,
        ]);
    }

    /**
     * Add item to cart
     */
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'product_id' => 'required|exists:products,id',
            'quantity' => 'sometimes|integer|min:1',
        ]);

        $user = $request->user();
        $productId = $request->input('product_id');
        $quantity = $request->input('quantity', 1);

        // Get or create cart for user
        $cart = Cart::firstOrCreate(['user_id' => $user->id]);

        // Check if item already exists in cart
        $cartItem = CartItem::where('cart_id', $cart->id)
            ->where('product_id', $productId)
            ->first();

        if ($cartItem) {
            // Item exists, increment quantity
            $cartItem->quantity += $quantity;
            $cartItem->save();
        } else {
            // Create new cart item
            $cartItem = CartItem::create([
                'cart_id' => $cart->id,
                'product_id' => $productId,
                'quantity' => $quantity,
            ]);
        }

        // Load product details
        $cartItem->load('product');

        return response()->json([
            'success' => true,
            'message' => 'Item added to cart successfully',
            'data' => [
                'id' => $cartItem->id,
                'product_id' => $cartItem->product_id,
                'quantity' => $cartItem->quantity,
            ],
        ], 201);
    }

    /**
     * Update cart item quantity
     */
    public function update(Request $request, $itemId): JsonResponse
    {
        $request->validate([
            'quantity' => 'required|integer|min:1',
        ]);

        $user = $request->user();
        $cart = Cart::where('user_id', $user->id)->first();

        if (! $cart) {
            return response()->json([
                'success' => false,
                'message' => 'Cart not found',
            ], 404);
        }

        $cartItem = CartItem::where('cart_id', $cart->id)
            ->where('id', $itemId)
            ->first();

        if (! $cartItem) {
            return response()->json([
                'success' => false,
                'message' => 'Cart item not found',
            ], 404);
        }

        $cartItem->quantity = $request->input('quantity');
        $cartItem->save();

        return response()->json([
            'success' => true,
            'message' => 'Cart item updated successfully',
            'data' => [
                'id' => $cartItem->id,
                'quantity' => $cartItem->quantity,
            ],
        ]);
    }

    /**
     * Remove item from cart
     */
    public function destroy(Request $request, $itemId): JsonResponse
    {
        $user = $request->user();
        $cart = Cart::where('user_id', $user->id)->first();

        if (! $cart) {
            return response()->json([
                'success' => false,
                'message' => 'Cart not found',
            ], 404);
        }

        $cartItem = CartItem::where('cart_id', $cart->id)
            ->where('id', $itemId)
            ->first();

        if (! $cartItem) {
            return response()->json([
                'success' => false,
                'message' => 'Cart item not found',
            ], 404);
        }

        $cartItem->delete();

        return response()->json([
            'success' => true,
            'message' => 'Item removed from cart successfully',
        ]);
    }
}
