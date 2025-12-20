<?php

namespace App\Http\Controllers\Seller;

use App\Http\Controllers\Controller;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ProductController extends Controller
{
    /**
     * Display a listing of seller's products.
     */
    public function index(Request $request)
    {
        $perPage = $request->query('per_page', 15);

        $products = Product::with('category')
            ->where('seller_id', auth()->id())
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);

        return response()->json([
            'products' => $products,
        ]);
    }

    /**
     * Store a newly created product.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'category_id' => ['required', 'exists:categories,id'],
            'description' => ['nullable', 'string'],
            'price' => ['required', 'numeric', 'min:0'],
            'stock_quantity' => ['required', 'integer', 'min:0'],
            'image' => ['required', 'file', 'image', 'max:2048'],
        ]);

        // Generate slug from name
        $validated['slug'] = Str::slug($validated['name']);

        // Ensure slug is unique
        $originalSlug = $validated['slug'];
        $counter = 1;
        while (Product::where('slug', $validated['slug'])->exists()) {
            $validated['slug'] = $originalSlug.'-'.$counter;
            $counter++;
        }

        // Handle image upload
        if ($request->hasFile('image')) {
            $image = $request->file('image');
            $filename = time().'_'.Str::random(10).'.'.$image->getClientOriginalExtension();
            $path = $image->storeAs('products', $filename, 'public');
            // Store only the relative path (e.g., 'products/filename.jpg')
            // The Product model accessor will transform it to full URL
            $validated['image_url'] = $path;
        }

        // Automatically set seller_id
        $validated['seller_id'] = auth()->id();

        $product = Product::create($validated);

        return response()->json([
            'message' => 'Product created successfully',
            'product' => $product->load('category'),
        ], 201);
    }

    /**
     * Display the specified product.
     */
    public function show($id)
    {
        $product = Product::with('category')
            ->where('seller_id', auth()->id())
            ->findOrFail($id);

        return response()->json([
            'product' => $product,
        ]);
    }

    /**
     * Update the specified product.
     */
    public function update(Request $request, $id)
    {
        $product = Product::where('seller_id', auth()->id())->findOrFail($id);

        $validated = $request->validate([
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'category_id' => ['sometimes', 'required', 'exists:categories,id'],
            'description' => ['nullable', 'string'],
            'price' => ['sometimes', 'required', 'numeric', 'min:0'],
            'stock_quantity' => ['sometimes', 'required', 'integer', 'min:0'],
            'image' => ['nullable', 'file', 'image', 'max:2048'],
        ]);

        // Update slug if name changed
        if (isset($validated['name']) && $validated['name'] !== $product->name) {
            $validated['slug'] = Str::slug($validated['name']);

            // Ensure slug is unique
            $originalSlug = $validated['slug'];
            $counter = 1;
            while (Product::where('slug', $validated['slug'])->where('id', '!=', $product->id)->exists()) {
                $validated['slug'] = $originalSlug.'-'.$counter;
                $counter++;
            }
        }

        // Handle image upload
        if ($request->hasFile('image')) {
            // Delete old image if exists (only for local files, not external URLs)
            if ($product->image_url) {
                $rawImageUrl = $product->getAttributes()['image_url'];
                if (! str_starts_with($rawImageUrl, 'http://') && ! str_starts_with($rawImageUrl, 'https://')) {
                    // It's a local path, delete it
                    $pathToDelete = str_replace('/storage/', '', $rawImageUrl);
                    Storage::disk('public')->delete($pathToDelete);
                }
            }

            $image = $request->file('image');
            $filename = time().'_'.Str::random(10).'.'.$image->getClientOriginalExtension();
            $path = $image->storeAs('products', $filename, 'public');
            // Store only the relative path (e.g., 'products/filename.jpg')
            // The Product model accessor will transform it to full URL
            $validated['image_url'] = $path;
        }

        $product->update($validated);

        return response()->json([
            'message' => 'Product updated successfully',
            'product' => $product->load('category'),
        ]);
    }

    /**
     * Remove the specified product.
     */
    public function destroy($id)
    {
        $product = Product::where('seller_id', auth()->id())->findOrFail($id);

        // Delete image if exists (only for local files, not external URLs)
        if ($product->image_url) {
            $rawImageUrl = $product->getAttributes()['image_url'];
            if (! str_starts_with($rawImageUrl, 'http://') && ! str_starts_with($rawImageUrl, 'https://')) {
                // It's a local path, delete it
                $pathToDelete = str_replace('/storage/', '', $rawImageUrl);
                Storage::disk('public')->delete($pathToDelete);
            }
        }

        $product->delete();

        return response()->json([
            'message' => 'Product deleted successfully',
        ]);
    }
}
