<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    /**
     * Get paginated products with filters
     *
     * Supported filters:
     * - search: Partial match on name/description
     * - category_id: Exact match
     * - min_price / max_price: Range filter
     * - sort: 'newest', 'price_asc', 'price_desc'
     * - limit: Custom pagination limit (useful for homepage)
     */
    public function index(Request $request): JsonResponse
    {
        $query = Product::query()
            ->with(['seller.seller', 'category']);

        // Search filter (name or description)
        if ($request->has('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
                    //orWhereHas cho bang ben ngoaiorWhereHas('seller', function ($q_seller) use ($search) {
                    // Giả sử cột tên người bán trong database là 'name'
                    //$q_seller->where('name', 'like', "%{$search}%");
            });
        }

        // Category filter
        if ($request->has('category_id')) {
            $query->where('category_id', $request->input('category_id'));
        }

        // Price range filters
        if ($request->has('min_price')) {
            $query->where('price', '>=', $request->input('min_price'));
        }

        if ($request->has('max_price')) {
            $query->where('price', '<=', $request->input('max_price'));
        }

        // Sorting
        $sort = $request->input('sort', 'newest');
        switch ($sort) {
            case 'price_asc':
                $query->orderBy('price', 'asc');
                break;
            case 'price_desc':
                $query->orderBy('price', 'desc');
                break;
            case 'newest':
            default:
                $query->orderBy('created_at', 'desc');
                break;
        }

        // Pagination with custom limit support
        $limit = $request->input('limit', 15);
        $products = $query->paginate($limit);

        return response()->json($products);
    }

    /**
     * Get single product by ID with relationships
     */
    public function show(string $id): JsonResponse
    {
        $product = Product::with(['seller.seller', 'category'])
            ->findOrFail($id);

        return response()->json([
            'data' => $product,
        ]);
    }
}
