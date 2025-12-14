<?php

namespace App\Http\Controllers;

use App\Models\Category;
use Illuminate\Http\JsonResponse;

class CategoryController extends Controller
{
    /**
     * Get all categories with optimized fields
     */
    public function index(): JsonResponse
    {
        $categories = Category::select(['id', 'name', 'slug', 'icon_class'])
            ->orderBy('name')
            ->get();

        return response()->json([
            'data' => $categories,
        ]);
    }
}
