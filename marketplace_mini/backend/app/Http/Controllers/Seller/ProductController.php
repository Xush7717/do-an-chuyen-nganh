<?php

namespace App\Http\Controllers\Seller;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
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

        $products = Product::with(['category', 'variants'])
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
        // --- BƯỚC 1: XỬ LÝ DỮ LIỆU JSON TỪ FORMDATA ---
        // FormData gửi 'options' và 'attributes' dưới dạng chuỗi JSON string.
        // Phải decode nó thành Mảng PHP thì Validation mới hiểu được.

        // 1. Giải mã 'options'
        if ($request->has('options') && is_string($request->input('options'))) {
            $request->merge([
                'options' => json_decode($request->input('options'), true),
            ]);
        }

        // 2. Giải mã 'attributes' bên trong từng variant
        $variants = $request->input('variants', []);
        if (is_array($variants)) {
            foreach ($variants as $key => $variant) {
                // Kiểm tra xem attributes có phải là chuỗi JSON không
                if (isset($variant['attributes']) && is_string($variant['attributes'])) {
                    $variants[$key]['attributes'] = json_decode($variant['attributes'], true);
                }
            }
            // Ghi đè lại dữ liệu variants đã decode vào request
            $request->merge(['variants' => $variants]);
        }

        // Kiểm tra xem có variants hay không dựa trên dữ liệu đã decode
        $hasVariants = ! empty($request->input('options')) && ! empty($request->input('variants'));

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'category_id' => ['required', 'exists:categories,id'],
            'description' => ['nullable', 'string'],

            // Logic kiểm tra: Nếu có variants thì price/stock bên ngoài được phép null
            'price' => [$hasVariants ? 'nullable' : 'required', 'numeric', 'min:0'],
            'stock_quantity' => [$hasVariants ? 'nullable' : 'required', 'integer', 'min:0'],

            'image' => ['required', 'file', 'image', 'max:2048'],

            'options' => ['nullable', 'array'], // Giờ nó đã là array nhờ bước 1
            'options.*.name' => ['required_with:options', 'string'],
            'options.*.values' => ['required_with:options', 'array'],

            'variants' => ['nullable', 'array'],
            'variants.*.sku' => ['required_with:variants', 'string'], 
            'variants.*.price' => ['required_with:variants', 'numeric', 'min:0'],
            'variants.*.stock_quantity' => ['required_with:variants', 'integer', 'min:0'],
            'variants.*.attributes' => ['required_with:variants', 'array'], // Giờ nó đã là array nhờ bước 1
            'variants.*.image' => ['nullable', 'file', 'image', 'max:2048'],
        ]);

        return DB::transaction(function () use ($request, $validated, $hasVariants) {
            // Generate slug
            $validated['slug'] = Str::slug($validated['name']);
            $originalSlug = $validated['slug'];
            $counter = 1;
            while (Product::where('slug', $validated['slug'])->exists()) {
                $validated['slug'] = $originalSlug.'-'.$counter;
                $counter++;
            }

            // Upload ảnh chính
            if ($request->hasFile('image')) {
                $image = $request->file('image');
                $filename = time().'_'.Str::random(10).'.'.$image->getClientOriginalExtension();
                $path = $image->storeAs('products', $filename, 'public');
                $validated['image_url'] = $path; // Lưu ý: Model của bạn cần accessor để nối full URL
            }

            $validated['seller_id'] = auth()->id();
            $validated['has_variants'] = $hasVariants;

            // Nếu là sản phẩm biến thể, lấy giá của biến thể đầu tiên làm giá hiển thị
            if ($hasVariants && isset($validated['variants']) && count($validated['variants']) > 0) {
                $validated['price'] = $validated['variants'][0]['price'];
                $validated['stock_quantity'] = array_sum(array_column($validated['variants'], 'stock_quantity'));
            }

            // Tạo Product
            $product = Product::create($validated);

            // Tạo Variants
            if ($hasVariants && isset($validated['variants'])) {
                foreach ($validated['variants'] as $index => $variantData) {
                    $variantImagePath = null;

                    // Upload ảnh biến thể
                    if ($request->hasFile("variants.{$index}.image")) {
                        $variantImage = $request->file("variants.{$index}.image");
                        $variantFilename = time().'_v'.Str::random(10).'.'.$variantImage->getClientOriginalExtension();
                        $variantImagePath = $variantImage->storeAs('product_variants', $variantFilename, 'public');
                    }

                    ProductVariant::create([
                        'product_id' => $product->id,
                        'sku' => $variantData['sku'],
                        'price' => $variantData['price'],
                        'stock_quantity' => $variantData['stock_quantity'],
                        'attributes' => $variantData['attributes'], // Đây giờ là mảng, Model phải có protected $casts = ['attributes' => 'array']
                        'image_url' => $variantImagePath,
                    ]);
                }
            }

            return response()->json([
                'message' => 'Product created successfully',
                'product' => $product->load(['variants']),
            ], 201);
        });
    }

    // public function store(Request $request)
    // {
    //     $hasVariants = $request->has('options') && $request->has('variants');

    //     $validated = $request->validate([
    //         'name' => ['required', 'string', 'max:255'],
    //         'category_id' => ['required', 'exists:categories,id'],
    //         'description' => ['nullable', 'string'],
    //         'price' => [$hasVariants ? 'nullable' : 'required', 'numeric', 'min:0'],
    //         'stock_quantity' => [$hasVariants ? 'nullable' : 'required', 'integer', 'min:0'],
    //         'image' => ['required', 'file', 'image', 'max:2048'],
    //         'options' => ['nullable', 'array'],
    //         'options.*.name' => ['required_with:options', 'string'],
    //         'options.*.values' => ['required_with:options', 'array'],
    //         'variants' => ['nullable', 'array', 'min:1'],
    //         'variants.*.sku' => ['required_with:variants', 'string', 'unique:product_variants,sku'],
    //         'variants.*.price' => ['required_with:variants', 'numeric', 'min:0'],
    //         'variants.*.stock_quantity' => ['required_with:variants', 'integer', 'min:0'],
    //         'variants.*.attributes' => ['required_with:variants', 'array'],
    //         'variants.*.image' => ['nullable', 'file', 'image', 'max:2048'],
    //     ]);

    //     return DB::transaction(function () use ($request, $validated, $hasVariants) {
    //         // Generate slug from name
    //         $validated['slug'] = Str::slug($validated['name']);

    //         // Ensure slug is unique
    //         $originalSlug = $validated['slug'];
    //         $counter = 1;
    //         while (Product::where('slug', $validated['slug'])->exists()) {
    //             $validated['slug'] = $originalSlug.'-'.$counter;
    //             $counter++;
    //         }

    //         // Handle main product image upload
    //         if ($request->hasFile('image')) {
    //             $image = $request->file('image');
    //             $filename = time().'_'.Str::random(10).'.'.$image->getClientOriginalExtension();
    //             $path = $image->storeAs('products', $filename, 'public');
    //             $validated['image_url'] = $path;
    //         }

    //         // Set seller_id and has_variants flag
    //         $validated['seller_id'] = auth()->id();
    //         $validated['has_variants'] = $hasVariants;

    //         // For variant products, use first variant price or set to 0
    //         if ($hasVariants) {
    //             $validated['price'] = $validated['variants'][0]['price'] ?? 0;
    //             $validated['stock_quantity'] = array_sum(array_column($validated['variants'], 'stock_quantity'));
    //         }

    //         // Create the product
    //         $product = Product::create($validated);

    //         // If has variants, create variant records
    //         if ($hasVariants && isset($validated['variants'])) {
    //             foreach ($validated['variants'] as $index => $variantData) {
    //                 $variantImagePath = null;

    //                 // Handle variant-specific image upload
    //                 if ($request->hasFile("variants.{$index}.image")) {
    //                     $variantImage = $request->file("variants.{$index}.image");
    //                     $variantFilename = time().'_'.Str::random(10).'.'.$variantImage->getClientOriginalExtension();
    //                     $variantImagePath = $variantImage->storeAs('product_variants', $variantFilename, 'public');
    //                 }

    //                 ProductVariant::create([
    //                     'product_id' => $product->id,
    //                     'sku' => $variantData['sku'],
    //                     'price' => $variantData['price'],
    //                     'stock_quantity' => $variantData['stock_quantity'],
    //                     'attributes' => $variantData['attributes'],
    //                     'image_url' => $variantImagePath,
    //                 ]);
    //             }
    //         }

    //         return response()->json([
    //             'message' => 'Product created successfully',
    //             'product' => $product->load(['category', 'variants']),
    //         ], 201);
    //     });
    // }

    /**
     * Display the specified product.
     */
    public function show($id)
    {
        $product = Product::with(['category', 'variants'])
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

        // --- DECODE JSON FROM FORMDATA (Same as store method) ---
        // 1. Decode 'options'
        if ($request->has('options') && is_string($request->input('options'))) {
            $request->merge([
                'options' => json_decode($request->input('options'), true),
            ]);
        }

        // 2. Decode 'attributes' inside each variant
        $variants = $request->input('variants', []);
        if (is_array($variants)) {
            foreach ($variants as $key => $variant) {
                if (isset($variant['attributes']) && is_string($variant['attributes'])) {
                    $variants[$key]['attributes'] = json_decode($variant['attributes'], true);
                }
            }
            $request->merge(['variants' => $variants]);
        }

        $hasVariants = $request->has('options') && $request->has('variants');

        $validated = $request->validate([
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'category_id' => ['sometimes', 'required', 'exists:categories,id'],
            'description' => ['nullable', 'string'],
            'price' => [($product->has_variants || $hasVariants) ? 'nullable' : 'sometimes|required', 'numeric', 'min:0'],
            'stock_quantity' => [($product->has_variants || $hasVariants) ? 'nullable' : 'sometimes|required', 'integer', 'min:0'],
            'image' => ['nullable', 'file', 'image', 'max:2048'],
            'options' => ['nullable', 'array'],
            'options.*.name' => ['required_with:options', 'string'],
            'options.*.values' => ['required_with:options', 'array'],
            'variants' => ['nullable', 'array'],
            'variants.*.id' => ['nullable', 'exists:product_variants,id'],
            'variants.*.sku' => ['required_with:variants', 'string'],
            'variants.*.price' => ['required_with:variants', 'numeric', 'min:0'],
            'variants.*.stock_quantity' => ['required_with:variants', 'integer', 'min:0'],
            'variants.*.attributes' => ['required_with:variants', 'array'],
            'variants.*.image' => ['nullable', 'file', 'image', 'max:2048'],
        ]);

        return DB::transaction(function () use ($request, $validated, $product, $hasVariants) {
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

            // Handle main product image upload
            if ($request->hasFile('image')) {
                // Delete old image if exists (only for local files, not external URLs)
                if ($product->image_url) {
                    $rawImageUrl = $product->getAttributes()['image_url'];
                    if (! str_starts_with($rawImageUrl, 'http://') && ! str_starts_with($rawImageUrl, 'https://')) {
                        $pathToDelete = str_replace('/storage/', '', $rawImageUrl);
                        Storage::disk('public')->delete($pathToDelete);
                    }
                }

                $image = $request->file('image');
                $filename = time().'_'.Str::random(10).'.'.$image->getClientOriginalExtension();
                $path = $image->storeAs('products', $filename, 'public');
                $validated['image_url'] = $path;
            }

            // Update has_variants flag
            $validated['has_variants'] = $hasVariants;

            // For variant products, recalculate aggregated price and stock
            if ($hasVariants && isset($validated['variants'])) {
                $validated['price'] = $validated['variants'][0]['price'] ?? $product->price;
                $validated['stock_quantity'] = array_sum(array_column($validated['variants'], 'stock_quantity'));
            }

            // Update the product
            $product->update($validated);

            // Sync variants if provided
            if ($hasVariants && isset($validated['variants'])) {
                $existingVariantIds = [];

                foreach ($validated['variants'] as $index => $variantData) {
                    $variantImagePath = null;

                    // Handle variant-specific image upload
                    if ($request->hasFile("variants.{$index}.image")) {
                        $variantImage = $request->file("variants.{$index}.image");
                        $variantFilename = time().'_'.Str::random(10).'.'.$variantImage->getClientOriginalExtension();
                        $variantImagePath = $variantImage->storeAs('product_variants', $variantFilename, 'public');
                    }

                    // Update existing or create new variant
                    if (isset($variantData['id'])) {
                        $variant = ProductVariant::where('id', $variantData['id'])
                            ->where('product_id', $product->id)
                            ->first();

                        if ($variant) {
                            $updateData = [
                                'sku' => $variantData['sku'],
                                'price' => $variantData['price'],
                                'stock_quantity' => $variantData['stock_quantity'],
                                'attributes' => $variantData['attributes'],
                            ];

                            if ($variantImagePath) {
                                $updateData['image_url'] = $variantImagePath;
                            }

                            $variant->update($updateData);
                            $existingVariantIds[] = $variant->id;
                        }
                    } else {
                        $newVariant = ProductVariant::create([
                            'product_id' => $product->id,
                            'sku' => $variantData['sku'],
                            'price' => $variantData['price'],
                            'stock_quantity' => $variantData['stock_quantity'],
                            'attributes' => $variantData['attributes'],
                            'image_url' => $variantImagePath,
                        ]);
                        $existingVariantIds[] = $newVariant->id;
                    }
                }

                // Delete variants that are no longer in the list
                ProductVariant::where('product_id', $product->id)
                    ->whereNotIn('id', $existingVariantIds)
                    ->delete();
            }

            return response()->json([
                'message' => 'Product updated successfully',
                'product' => $product->load(['category', 'variants']),
            ]);
        });
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
