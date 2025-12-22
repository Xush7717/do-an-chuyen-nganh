<?php

namespace App\Http\Controllers;

use App\Models\Order;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class OrderController extends Controller
{
    /**
     * Get authenticated user's orders (paginated, latest first)
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $user = $request->user();

            $orders = Order::where('user_id', $user->id)
                ->with([
                    'orderItems.product:id,name,image_url',
                    'payment',
                ])
                ->orderBy('created_at', 'desc')
                ->paginate(10);

            return response()->json([
                'success' => true,
                'data' => $orders,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch orders',
            ], 500);
        }
    }

    /**
     * Get a single order by ID (with authorization check)
     */
    public function show(Request $request, int $id): JsonResponse
    {
        try {
            $user = $request->user();

            $order = Order::with([
                    'orderItems.product:id,name,image_url',
                    'payment',
                ])
                ->find($id);

            if (! $order) {
                return response()->json([
                    'success' => false,
                    'message' => 'Order not found',
                ], 404);
            }

            // Security check: Ensure order belongs to authenticated user
            if ($order->user_id !== $user->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized access',
                ], 403);
            }

            // Add is_reviewed flag to each order item
            $order->orderItems->each(function ($item) use ($user) {
                $item->is_reviewed = \App\Models\Review::where('user_id', $user->id)
                    ->where('product_id', $item->product_id)
                    ->exists();
            });

            return response()->json([
                'success' => true,
                'data' => $order,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch order',
            ], 500);
        }
    }
}
