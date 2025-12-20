<?php

namespace App\Http\Controllers\Seller;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Product;
use Illuminate\Http\Request;

class OrderController extends Controller
{
    /**
     * Display a listing of orders containing seller's products.
     */
    public function index(Request $request)
    {
        $sellerId = auth()->id();
        $perPage = $request->query('per_page', 15);

        // Get all product IDs belonging to this seller
        $sellerProductIds = Product::where('seller_id', $sellerId)->pluck('id');

        // Get orders that contain at least one item from this seller
        $orders = Order::with([
            'user:id,name,email',
            'orderItems' => function ($query) use ($sellerProductIds) {
                // Only load items that belong to this seller
                $query->whereIn('product_id', $sellerProductIds)
                    ->with('product:id,name,image_url');
            },
        ])
            ->whereHas('orderItems', function ($query) use ($sellerProductIds) {
                $query->whereIn('product_id', $sellerProductIds);
            })
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);

        return response()->json([
            'orders' => $orders,
        ]);
    }

    /**
     * Display the specified order with only seller's items.
     */
    public function show($id)
    {
        $sellerId = auth()->id();

        // Get all product IDs belonging to this seller
        $sellerProductIds = Product::where('seller_id', $sellerId)->pluck('id');

        $order = Order::with([
            'user:id,name,email',
            'orderItems' => function ($query) use ($sellerProductIds) {
                $query->whereIn('product_id', $sellerProductIds)
                    ->with('product:id,name,image_url');
            },
        ])
            ->whereHas('orderItems', function ($query) use ($sellerProductIds) {
                $query->whereIn('product_id', $sellerProductIds);
            })
            ->findOrFail($id);

        return response()->json([
            'order' => $order,
        ]);
    }

    /**
     * Update the status of an order.
     */
    public function updateStatus(Request $request, $id)
    {
        $sellerId = auth()->id();

        // Get all product IDs belonging to this seller
        $sellerProductIds = Product::where('seller_id', $sellerId)->pluck('id');

        // Verify seller has items in this order
        $order = Order::whereHas('orderItems', function ($query) use ($sellerProductIds) {
            $query->whereIn('product_id', $sellerProductIds);
        })->findOrFail($id);

        // Validate status
        $validated = $request->validate([
            'status' => ['required', 'in:pending,processing,shipped,delivered,cancelled'],
        ]);

        // Capture old status before updating
        $oldStatus = $order->status;
        $newStatus = $validated['status'];

        // Restore stock if order is being cancelled (and wasn't already cancelled)
        if ($newStatus === 'cancelled' && $oldStatus !== 'cancelled') {
            // Get all order items belonging to this seller
            $sellerOrderItems = $order->orderItems()
                ->whereIn('product_id', $sellerProductIds)
                ->with('product')
                ->get();

            // Restore stock for each item
            foreach ($sellerOrderItems as $orderItem) {
                if ($orderItem->product) {
                    $orderItem->product->increment('stock_quantity', $orderItem->quantity);

                    \Log::info('Stock restored due to cancellation', [
                        'order_id' => $order->id,
                        'product_id' => $orderItem->product->id,
                        'product_name' => $orderItem->product->name,
                        'quantity_restored' => $orderItem->quantity,
                        'new_stock' => $orderItem->product->fresh()->stock_quantity,
                    ]);
                }
            }
        }

        // Update order status
        $order->update([
            'status' => $newStatus,
        ]);

        // Reload order with relationships
        $order->load([
            'user:id,name,email',
            'orderItems' => function ($query) use ($sellerProductIds) {
                $query->whereIn('product_id', $sellerProductIds)
                    ->with('product:id,name,image_url');
            },
        ]);

        return response()->json([
            'message' => 'Order status updated successfully',
            'order' => $order,
        ]);
    }

    /**
     * Get dashboard statistics for seller.
     */
    public function stats()
    {
        $sellerId = auth()->id();

        // Get all product IDs belonging to this seller
        $sellerProductIds = Product::where('seller_id', $sellerId)->pluck('id');

        // Count total products
        $totalProducts = Product::where('seller_id', $sellerId)->count();

        // Count total orders containing seller's items
        $totalOrders = Order::whereHas('orderItems', function ($query) use ($sellerProductIds) {
            $query->whereIn('product_id', $sellerProductIds);
        })->count();

        // Calculate total revenue from seller's items
        $totalRevenue = Order::whereHas('orderItems', function ($query) use ($sellerProductIds) {
            $query->whereIn('product_id', $sellerProductIds);
        })
            ->with(['orderItems' => function ($query) use ($sellerProductIds) {
                $query->whereIn('product_id', $sellerProductIds);
            }])
            ->get()
            ->sum(function ($order) {
                return $order->orderItems->sum(function ($item) {
                    return $item->quantity * $item->price_at_purchase;
                });
            });

        // Get recent orders (last 5)
        $recentOrders = Order::with([
            'user:id,name',
            'orderItems' => function ($query) use ($sellerProductIds) {
                $query->whereIn('product_id', $sellerProductIds);
            },
        ])
            ->whereHas('orderItems', function ($query) use ($sellerProductIds) {
                $query->whereIn('product_id', $sellerProductIds);
            })
            ->orderBy('created_at', 'desc')
            ->take(5)
            ->get();

        return response()->json([
            'stats' => [
                'total_products' => $totalProducts,
                'total_orders' => $totalOrders,
                'total_revenue' => number_format($totalRevenue, 2),
            ],
            'recent_orders' => $recentOrders,
        ]);
    }
}
