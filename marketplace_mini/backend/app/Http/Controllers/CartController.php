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
            ->with(['product.category', 'product.seller', 'productVariant'])
            ->get()
            ->map(function ($item) {
                $price = $item->product->price;
                $imageUrl = $item->product->image_url;
                $variantInfo = null;

                // If item has variant, use variant-specific data
                if ($item->product_variant_id && $item->productVariant) {
                    $price = $item->productVariant->price;
                    $imageUrl = $item->productVariant->image_url ?? $item->product->image_url;
                    $variantInfo = $item->variant_attributes;
                }

                return [
                    'id' => $item->id,
                    'product_id' => $item->product_id,
                    'product_variant_id' => $item->product_variant_id,
                    'variant_attributes' => $variantInfo,
                    'quantity' => $item->quantity,
                    'product' => [
                        'id' => $item->product->id,
                        'name' => $item->product->name,
                        'price' => $price,
                        'image_url' => $imageUrl,
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
            'product_variant_id' => 'nullable|exists:product_variants,id',
            'variant_attributes' => 'nullable|array',
            'quantity' => 'sometimes|integer|min:1',
        ]);

        $user = $request->user();
        $productId = $request->input('product_id');
        $variantId = $request->input('product_variant_id');
        $variantAttributes = $request->input('variant_attributes');
        $quantity = $request->input('quantity', 1);

        // Get or create cart for user
        $cart = Cart::firstOrCreate(['user_id' => $user->id]);

        // Check if item already exists in cart (match both product_id and variant_id)
        $query = CartItem::where('cart_id', $cart->id)
            ->where('product_id', $productId);

        if ($variantId) {
            $query->where('product_variant_id', $variantId);
        } else {
            $query->whereNull('product_variant_id');
        }

        $cartItem = $query->first();

        if ($cartItem) {
            // Item exists, increment quantity
            $cartItem->quantity += $quantity;
            $cartItem->save();
        } else {
            // Create new cart item
            $cartItem = CartItem::create([
                'cart_id' => $cart->id,
                'product_id' => $productId,
                'product_variant_id' => $variantId,
                'variant_attributes' => $variantAttributes,
                'quantity' => $quantity,
            ]);
        }

        // Load product details
        $cartItem->load(['product', 'productVariant']);

        return response()->json([
            'success' => true,
            'message' => 'Item added to cart successfully',
            'data' => [
                'id' => $cartItem->id,
                'product_id' => $cartItem->product_id,
                'product_variant_id' => $cartItem->product_variant_id,
                'variant_attributes' => $cartItem->variant_attributes,
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
