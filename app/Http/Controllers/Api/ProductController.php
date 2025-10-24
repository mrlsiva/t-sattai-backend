<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class ProductController extends Controller
{
    /**
     * Display a listing of products
     */
    public function index(Request $request)
    {
        $query = Product::with(['category'])
            ->where('is_active', true)
            ->where('stock', '>', 0);

        // Filter by category
        if ($request->has('category_id')) {
            $query->where('category_id', $request->category_id);
        }

        // Search by name or description
        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%")
                  ->orWhere('sku', 'like', "%{$search}%");
            });
        }

        // Filter by price range
        if ($request->has('min_price')) {
            $query->where('price', '>=', $request->min_price);
        }
        if ($request->has('max_price')) {
            $query->where('price', '<=', $request->max_price);
        }

        // Sort products
        $sortBy = $request->get('sort_by', 'created_at');
        $sortOrder = $request->get('sort_order', 'desc');
        
        $allowedSortFields = ['name', 'price', 'created_at', 'updated_at'];
        if (in_array($sortBy, $allowedSortFields)) {
            $query->orderBy($sortBy, $sortOrder);
        }

        $products = $query->paginate($request->get('per_page', 12));

        return response()->json([
            'success' => true,
            'data' => $products,
            'filters' => [
                'categories' => Category::where('is_active', true)->get(['id', 'name']),
                'price_range' => [
                    'min' => Product::where('is_active', true)->min('price'),
                    'max' => Product::where('is_active', true)->max('price'),
                ]
            ]
        ]);
    }

    /**
     * Display the specified product
     */
    public function show(Product $product)
    {
        if (!$product->is_active) {
            return response()->json([
                'success' => false,
                'message' => 'Product not found'
            ], 404);
        }

        $product->load(['category', 'reviews.user']);

        // Calculate average rating
        $avgRating = $product->reviews()->avg('rating');
        $totalReviews = $product->reviews()->count();

        return response()->json([
            'success' => true,
            'data' => [
                'product' => $product,
                'rating' => [
                    'average' => round($avgRating, 1),
                    'total_reviews' => $totalReviews
                ],
                'related_products' => Product::where('category_id', $product->category_id)
                    ->where('id', '!=', $product->id)
                    ->where('is_active', true)
                    ->limit(4)
                    ->get(['id', 'name', 'price', 'images'])
            ]
        ]);
    }

    /**
     * Get featured products
     */
    public function featured()
    {
        // First try to get actual featured products
        $featuredProducts = Product::where('is_active', true)
            ->where('is_featured', true)
            ->where('stock', '>', 0)
            ->with('category')
            ->limit(8)
            ->get();

        // If no featured products found, get latest active products
        if ($featuredProducts->isEmpty()) {
            $featuredProducts = Product::where('is_active', true)
                ->where('stock', '>', 0)
                ->with('category')
                ->orderBy('created_at', 'desc')
                ->limit(8)
                ->get();
        }

        return response()->json([
            'success' => true,
            'data' => $featuredProducts
        ]);
    }

    /**
     * Get all products for admin (including inactive and out of stock)
     */
    public function adminIndex(Request $request)
    {
        $query = Product::with(['category']);

        // Search by name or description
        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%")
                  ->orWhere('sku', 'like', "%{$search}%");
            });
        }

        // Filter by category
        if ($request->has('category_id')) {
            $query->where('category_id', $request->category_id);
        }

        // Filter by status
        if ($request->has('status')) {
            switch ($request->status) {
                case 'active':
                    $query->where('is_active', true);
                    break;
                case 'inactive':
                    $query->where('is_active', false);
                    break;
                case 'out_of_stock':
                    $query->where('stock', '<=', 0);
                    break;
            }
        }

        // Sort products
        $sortBy = $request->get('sort_by', 'created_at');
        $sortOrder = $request->get('sort_order', 'desc');
        
        $allowedSortFields = ['name', 'price', 'created_at', 'updated_at', 'stock'];
        if (in_array($sortBy, $allowedSortFields)) {
            $query->orderBy($sortBy, $sortOrder);
        }

        $products = $query->paginate($request->get('per_page', 12));

        return response()->json([
            'success' => true,
            'data' => $products
        ]);
    }

    /**
     * Store a newly created product (Admin only)
     */
    public function store(Request $request)
    {
        // Check if user is admin
        if (!$request->user()->is_admin) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized access'
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'description' => 'required|string',
            'short_description' => 'nullable|string|max:500',
            'price' => 'required|numeric|min:0',
            'sale_price' => 'nullable|numeric|min:0',
            'sku' => 'required|string|unique:products,sku',
            'stock' => 'required|integer|min:0',
            'category_id' => 'required|exists:categories,id',
            'images' => 'nullable|array',
            'weight' => 'nullable|numeric|min:0',
            'dimensions' => 'nullable|json',
            'specifications' => 'nullable|json',
            'is_featured' => 'boolean',
            'is_active' => 'boolean',
            'meta_title' => 'nullable|string|max:255',
            'meta_description' => 'nullable|string|max:500',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        $product = Product::create([
            'name' => $request->name,
            'slug' => Str::slug($request->name),
            'description' => $request->description,
            'short_description' => $request->short_description,
            'price' => $request->price,
            'sale_price' => $request->sale_price,
            'sku' => $request->sku,
            'stock' => $request->stock,
            'category_id' => $request->category_id,
            'images' => $request->images ?? [],
            'weight' => $request->weight,
            'dimensions' => $request->dimensions,
            'specifications' => $request->specifications,
            'is_featured' => $request->get('is_featured', false),
            'is_active' => $request->get('is_active', true),
            'meta_title' => $request->meta_title,
            'meta_description' => $request->meta_description,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Product created successfully',
            'data' => $product
        ], 201);
    }

    /**
     * Update the specified product (Admin only)
     */
    public function update(Request $request, Product $product)
    {
        // Check if user is admin
        if (!$request->user()->is_admin) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized access'
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|string|max:255',
            'description' => 'sometimes|string',
            'short_description' => 'sometimes|nullable|string|max:500',
            'price' => 'sometimes|numeric|min:0',
            'sale_price' => 'sometimes|nullable|numeric|min:0',
            'sku' => 'sometimes|string|unique:products,sku,' . $product->id,
            'stock' => 'sometimes|integer|min:0',
            'category_id' => 'sometimes|exists:categories,id',
            'images' => 'sometimes|nullable|array',
            'weight' => 'sometimes|nullable|numeric|min:0',
            'dimensions' => 'sometimes|nullable|json',
            'specifications' => 'sometimes|nullable|json',
            'is_featured' => 'sometimes|boolean',
            'is_active' => 'sometimes|boolean',
            'meta_title' => 'sometimes|nullable|string|max:255',
            'meta_description' => 'sometimes|nullable|string|max:500',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        $updateData = $request->only([
            'name', 'description', 'short_description', 'price', 'sale_price',
            'sku', 'stock', 'category_id', 'images',
            'weight', 'dimensions', 'specifications', 'is_featured', 'is_active',
            'meta_title', 'meta_description'
        ]);

        if (isset($updateData['name'])) {
            $updateData['slug'] = Str::slug($updateData['name']);
        }

        $product->update($updateData);

        return response()->json([
            'success' => true,
            'message' => 'Product updated successfully',
            'data' => $product->fresh()
        ]);
    }

    /**
     * Remove the specified product (Admin only)
     */
    public function destroy(Request $request, Product $product)
    {
        // Check if user is admin
        if (!$request->user()->is_admin) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized access'
            ], 403);
        }

        $product->delete();

        return response()->json([
            'success' => true,
            'message' => 'Product deleted successfully'
        ]);
    }
}