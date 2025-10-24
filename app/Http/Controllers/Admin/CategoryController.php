<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class CategoryController extends Controller
{
    /**
     * Display a listing of categories
     */
    public function index(Request $request)
    {
        $query = Category::with(['parent', 'children'])
            ->withCount('products');

        // Search functionality
        if ($request->has('search') && $request->search) {
            $query->where('name', 'like', '%' . $request->search . '%')
                  ->orWhere('description', 'like', '%' . $request->search . '%');
        }

        // Filter by status
        if ($request->has('status') && $request->status !== '') {
            $query->where('is_active', $request->status === 'active');
        }

        // Filter by parent
        if ($request->has('parent_only') && $request->parent_only) {
            $query->whereNull('parent_id');
        }

        $categories = $query->ordered()
            ->paginate($request->get('limit', 15));

        return response()->json([
            'success' => true,
            'data' => $categories->items(),
            'pagination' => [
                'current_page' => $categories->currentPage(),
                'per_page' => $categories->perPage(),
                'total' => $categories->total(),
                'last_page' => $categories->lastPage(),
                'from' => $categories->firstItem(),
                'to' => $categories->lastItem()
            ]
        ]);
    }

    /**
     * Store a newly created category
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255|unique:categories,name',
            'description' => 'nullable|string|max:1000',
            'parent_id' => 'nullable|exists:categories,id',
            'sort_order' => 'nullable|integer|min:0',
            'is_active' => 'boolean',
            'meta_title' => 'nullable|string|max:255',
            'meta_description' => 'nullable|string|max:500',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $data = $validator->validated();
        
        // Handle image upload
        if ($request->hasFile('image')) {
            $image = $request->file('image');
            $imageName = time() . '_' . Str::random(10) . '.' . $image->getClientOriginalExtension();
            
            // Store in public/storage/categories/
            $image->storeAs('public/categories', $imageName);
            $data['image'] = $imageName;
        }

        // Generate slug from name
        $data['slug'] = Str::slug($data['name']);

        $category = Category::create($data);
        $category->load(['parent', 'children']);

        return response()->json([
            'success' => true,
            'message' => 'Category created successfully',
            'data' => $category
        ], 201);
    }

    /**
     * Display the specified category
     */
    public function show(Category $category)
    {
        $category->load(['parent', 'children', 'products' => function($query) {
            $query->select('id', 'name', 'slug', 'price', 'sale_price', 'stock_quantity', 'is_featured', 'category_id')
                  ->where('status', 'active')
                  ->take(10);
        }]);

        return response()->json([
            'success' => true,
            'data' => $category
        ]);
    }

    /**
     * Update the specified category
     */
    public function update(Request $request, Category $category)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255|unique:categories,name,' . $category->id,
            'description' => 'nullable|string|max:1000',
            'parent_id' => 'nullable|exists:categories,id',
            'sort_order' => 'nullable|integer|min:0',
            'is_active' => 'boolean',
            'meta_title' => 'nullable|string|max:255',
            'meta_description' => 'nullable|string|max:500',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $data = $validator->validated();

        // Handle image upload
        if ($request->hasFile('image')) {
            // Delete old image if exists
            if ($category->image && Storage::exists('public/categories/' . $category->image)) {
                Storage::delete('public/categories/' . $category->image);
            }

            $image = $request->file('image');
            $imageName = time() . '_' . Str::random(10) . '.' . $image->getClientOriginalExtension();
            
            $image->storeAs('public/categories', $imageName);
            $data['image'] = $imageName;
        }

        // Generate slug from name if name changed
        if (isset($data['name']) && $data['name'] !== $category->name) {
            $data['slug'] = Str::slug($data['name']);
        }

        $category->update($data);
        $category->load(['parent', 'children']);

        return response()->json([
            'success' => true,
            'message' => 'Category updated successfully',
            'data' => $category
        ]);
    }

    /**
     * Remove the specified category
     */
    public function destroy(Category $category)
    {
        // Check if category has products
        if ($category->products()->count() > 0) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot delete category with existing products'
            ], 400);
        }

        // Check if category has subcategories
        if ($category->children()->count() > 0) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot delete category with subcategories'
            ], 400);
        }

        // Delete image if exists
        if ($category->image && Storage::exists('public/categories/' . $category->image)) {
            Storage::delete('public/categories/' . $category->image);
        }

        $category->delete();

        return response()->json([
            'success' => true,
            'message' => 'Category deleted successfully'
        ]);
    }

    /**
     * Update category image only
     */
    public function updateImage(Request $request, Category $category)
    {
        $validator = Validator::make($request->all(), [
            'image' => 'required|image|mimes:jpeg,png,jpg,gif,svg|max:2048'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid image file',
                'errors' => $validator->errors()
            ], 422);
        }

        // Delete old image if exists
        if ($category->image && Storage::exists('public/categories/' . $category->image)) {
            Storage::delete('public/categories/' . $category->image);
        }

        // Upload new image
        $image = $request->file('image');
        $imageName = time() . '_' . Str::random(10) . '.' . $image->getClientOriginalExtension();
        
        $image->storeAs('public/categories', $imageName);
        
        $category->update(['image' => $imageName]);

        return response()->json([
            'success' => true,
            'message' => 'Category image updated successfully',
            'data' => [
                'image' => $category->image,
                'display_image' => $category->display_image
            ]
        ]);
    }

    /**
     * Remove category image
     */
    public function removeImage(Category $category)
    {
        if ($category->image) {
            // Delete image file
            if (Storage::exists('public/categories/' . $category->image)) {
                Storage::delete('public/categories/' . $category->image);
            }

            // Update database
            $category->update(['image' => null]);

            return response()->json([
                'success' => true,
                'message' => 'Category image removed successfully',
                'data' => [
                    'image' => null,
                    'display_image' => $category->display_image
                ]
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => 'Category has no image to remove'
        ], 400);
    }

    /**
     * Get category statistics
     */
    public function stats()
    {
        $total = Category::count();
        $active = Category::where('is_active', true)->count();
        $inactive = Category::where('is_active', false)->count();
        $withImages = Category::whereNotNull('image')->count();
        $withoutImages = Category::whereNull('image')->count();
        $parentCategories = Category::whereNull('parent_id')->count();
        $subcategories = Category::whereNotNull('parent_id')->count();

        $topCategories = Category::withCount('products')
            ->where('is_active', true)
            ->orderBy('products_count', 'desc')
            ->take(5)
            ->get(['id', 'name', 'slug', 'image', 'products_count']);

        return response()->json([
            'success' => true,
            'data' => [
                'total' => $total,
                'active' => $active,
                'inactive' => $inactive,
                'withImages' => $withImages,
                'withoutImages' => $withoutImages,
                'parentCategories' => $parentCategories,
                'subcategories' => $subcategories,
                'topCategories' => $topCategories
            ]
        ]);
    }
}
