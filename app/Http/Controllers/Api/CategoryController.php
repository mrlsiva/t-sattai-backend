<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class CategoryController extends Controller
{
    /**
     * Display a listing of categories
     */
    public function index(Request $request)
    {
        $query = Category::where('is_active', true);

        // Get only parent categories if requested
        if ($request->get('parent_only', false)) {
            $query->whereNull('parent_id');
        }

        // Include subcategories if requested
        if ($request->get('with_children', false)) {
            $query->with('children');
        }

        $categories = $query->orderBy('sort_order')->get();

        return response()->json([
            'success' => true,
            'data' => $categories
        ]);
    }

    /**
     * Get all categories for admin (including inactive)
     */
    public function adminIndex(Request $request)
    {
        $query = Category::query();

        // Search by name
        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('slug', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
            });
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
            }
        }

        // Include subcategories if requested
        if ($request->get('with_children', false)) {
            $query->with('children');
        }

        // Include product count
        $query->withCount('products');

        $categories = $query->orderBy('sort_order')->get();

        return response()->json([
            'success' => true,
            'data' => $categories
        ]);
    }

    /**
     * Display the specified category
     */
    public function show(Category $category)
    {
        if (!$category->is_active) {
            return response()->json([
                'success' => false,
                'message' => 'Category not found'
            ], 404);
        }

        $category->load(['children', 'products' => function($query) {
            $query->where('is_active', true)->limit(12);
        }]);

        return response()->json([
            'success' => true,
            'data' => $category
        ]);
    }

    /**
     * Store a newly created category (Admin only)
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
            'description' => 'nullable|string',
            'image' => 'nullable|string',
            'parent_id' => 'nullable|exists:categories,id',
            'sort_order' => 'nullable|integer|min:0',
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

        $category = Category::create([
            'name' => $request->name,
            'slug' => Str::slug($request->name),
            'description' => $request->description,
            'image' => $request->image,
            'parent_id' => $request->parent_id,
            'sort_order' => $request->get('sort_order', 0),
            'is_active' => $request->get('is_active', true),
            'meta_title' => $request->meta_title,
            'meta_description' => $request->meta_description,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Category created successfully',
            'data' => $category
        ], 201);
    }

    /**
     * Update the specified category (Admin only)
     */
    public function update(Request $request, Category $category)
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
            'description' => 'sometimes|nullable|string',
            'image' => 'sometimes|nullable|string',
            'parent_id' => 'sometimes|nullable|exists:categories,id',
            'sort_order' => 'sometimes|nullable|integer|min:0',
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

        // Prevent setting parent to itself or its children
        if ($request->has('parent_id') && $request->parent_id) {
            if ($request->parent_id == $category->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Category cannot be its own parent'
                ], 400);
            }

            // Check if the new parent is a child of this category
            $childIds = $category->getAllChildrenIds();
            if (in_array($request->parent_id, $childIds)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot set a child category as parent'
                ], 400);
            }
        }

        $updateData = $request->only([
            'name', 'description', 'image', 'parent_id', 'sort_order',
            'is_active', 'meta_title', 'meta_description'
        ]);

        if (isset($updateData['name'])) {
            $updateData['slug'] = Str::slug($updateData['name']);
        }

        $category->update($updateData);

        return response()->json([
            'success' => true,
            'message' => 'Category updated successfully',
            'data' => $category->fresh()
        ]);
    }

    /**
     * Remove the specified category (Admin only)
     */
    public function destroy(Request $request, Category $category)
    {
        // Check if user is admin
        if (!$request->user()->is_admin) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized access'
            ], 403);
        }

        // Check if category has products
        if ($category->products()->count() > 0) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot delete category with existing products'
            ], 400);
        }

        // Check if category has children
        if ($category->children()->count() > 0) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot delete category with subcategories'
            ], 400);
        }

        $category->delete();

        return response()->json([
            'success' => true,
            'message' => 'Category deleted successfully'
        ]);
    }
}