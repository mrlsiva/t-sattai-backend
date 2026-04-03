<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Review;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ReviewController extends Controller
{
    /**
     * Get reviews for a product
     */
    public function index(Request $request, Product $product)
    {
        $reviews = Review::with(['user'])
            ->where('product_id', $product->id)
            ->where('is_approved', true)
            ->orderBy('created_at', 'desc')
            ->paginate(10);

        return response()->json([
            'success' => true,
            'data' => $reviews
        ]);
    }

    /**
     * Store a new review
     */
    public function store(Request $request, Product $product)
    {
        $validator = Validator::make($request->all(), [
            'rating' => 'required|integer|min:1|max:5',
            'title' => 'required|string|max:255',
            'comment' => 'required|string|max:1000',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        // Check if user already reviewed this product
        $existingReview = Review::where('user_id', $request->user()->id)
            ->where('product_id', $product->id)
            ->first();

        if ($existingReview) {
            return response()->json([
                'success' => false,
                'message' => 'You have already reviewed this product'
            ], 400);
        }

        $review = Review::create([
            'user_id' => $request->user()->id,
            'product_id' => $product->id,
            'rating' => $request->rating,
            'title' => $request->title,
            'comment' => $request->comment,
            'is_approved' => true, // Auto-approve for now
        ]);

        // Update product average rating
        $this->updateProductRating($product);

        $review->load('user');

        return response()->json([
            'success' => true,
            'message' => 'Review added successfully',
            'data' => $review
        ], 201);
    }

    /**
     * Update a review
     */
    public function update(Request $request, Review $review)
    {
        // Check if user owns this review
        if ($review->user_id !== $request->user()->id) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized'
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'rating' => 'required|integer|min:1|max:5',
            'title' => 'required|string|max:255',
            'comment' => 'required|string|max:1000',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        $review->update([
            'rating' => $request->rating,
            'title' => $request->title,
            'comment' => $request->comment,
        ]);

        // Update product average rating
        $this->updateProductRating($review->product);

        $review->load('user');

        return response()->json([
            'success' => true,
            'message' => 'Review updated successfully',
            'data' => $review
        ]);
    }

    /**
     * Delete a review
     */
    public function destroy(Request $request, Review $review)
    {
        // Check if user owns this review or is admin
        if ($review->user_id !== $request->user()->id && !$request->user()->is_admin) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized'
            ], 403);
        }

        $product = $review->product;
        $review->delete();

        // Update product average rating
        $this->updateProductRating($product);

        return response()->json([
            'success' => true,
            'message' => 'Review deleted successfully'
        ]);
    }

    /**
     * Show a single review
     */
    public function show(Review $review)
    {
        $review->load('user', 'product');

        return response()->json([
            'success' => true,
            'data' => $review,
        ]);
    }

    /**
     * Mark review as helpful
     */
    public function markHelpful(Request $request, Review $review)
    {
        $review->increment('helpful_count');

        return response()->json([
            'success' => true,
            'message' => 'Marked as helpful',
            'data' => ['helpful_count' => $review->helpful_count]
        ]);
    }

    /**
     * Admin: list all reviews (with optional filters)
     * GET /api/admin/reviews
     */
    public function adminIndex(Request $request)
    {
        $query = Review::with(['user', 'product']);

        if ($request->has('is_approved') && $request->is_approved !== '') {
            $query->where('is_approved', filter_var($request->is_approved, FILTER_VALIDATE_BOOLEAN));
        }

        if ($request->has('product_id')) {
            $query->where('product_id', $request->product_id);
        }

        if ($request->has('rating')) {
            $query->where('rating', $request->rating);
        }

        $reviews = $query->orderBy('created_at', 'desc')
            ->paginate($request->get('limit', 20));

        return response()->json([
            'success' => true,
            'data' => $reviews->items(),
            'pagination' => [
                'current_page' => $reviews->currentPage(),
                'per_page'     => $reviews->perPage(),
                'total'        => $reviews->total(),
                'last_page'    => $reviews->lastPage(),
            ],
        ]);
    }

    /**
     * Admin: approve a review
     * PUT /api/admin/reviews/{review}/approve
     */
    public function approve(Review $review)
    {
        $review->update(['is_approved' => true]);
        $this->updateProductRating($review->product);

        return response()->json([
            'success' => true,
            'message' => 'Review approved',
            'data'    => $review,
        ]);
    }

    /**
     * Admin: reject/unapprove a review
     * PUT /api/admin/reviews/{review}/reject
     */
    public function reject(Review $review)
    {
        $review->update(['is_approved' => false]);
        $this->updateProductRating($review->product);

        return response()->json([
            'success' => true,
            'message' => 'Review rejected',
            'data'    => $review,
        ]);
    }

    /**
     * Admin: delete any review
     * DELETE /api/admin/reviews/{review}
     */
    public function adminDestroy(Review $review)
    {
        $product = $review->product;
        $review->delete();
        $this->updateProductRating($product);

        return response()->json([
            'success' => true,
            'message' => 'Review deleted',
        ]);
    }

    /**
     * Update product average rating
     */
    private function updateProductRating(Product $product)
    {
        $averageRating = Review::where('product_id', $product->id)
            ->where('is_approved', true)
            ->avg('rating');

        $reviewCount = Review::where('product_id', $product->id)
            ->where('is_approved', true)
            ->count();

        $product->update([
            'average_rating' => $averageRating ? round($averageRating, 2) : 0,
            'review_count' => $reviewCount
        ]);
    }
}
