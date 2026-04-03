<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Coupon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class CouponController extends Controller
{
    /**
     * Validate a coupon code and return discount details
     * POST /api/coupons/validate
     */
    public function validate(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'code'    => 'required|string',
            'subtotal' => 'required|numeric|min:0',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors'  => $validator->errors(),
            ], 422);
        }

        $coupon = Coupon::where('code', strtoupper($request->code))->first();

        if (!$coupon) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid coupon code',
            ], 404);
        }

        if (!$coupon->isUsable($request->user()->id)) {
            return response()->json([
                'success' => false,
                'message' => 'This coupon is expired, inactive, or usage limit has been reached',
            ], 400);
        }

        if ($coupon->minimum_amount && $request->subtotal < $coupon->minimum_amount) {
            return response()->json([
                'success' => false,
                'message' => "Minimum order amount of ₹{$coupon->minimum_amount} required to use this coupon",
            ], 400);
        }

        $discount = $coupon->calculateDiscount((float) $request->subtotal);

        return response()->json([
            'success'  => true,
            'message'  => 'Coupon applied successfully',
            'data'     => [
                'code'             => $coupon->code,
                'name'             => $coupon->name,
                'description'      => $coupon->description,
                'type'             => $coupon->type,
                'value'            => (float) $coupon->value,
                'discount_amount'  => $discount,
                'new_subtotal'     => max(0, (float) $request->subtotal - $discount),
            ],
        ]);
    }

    /**
     * List all coupons (admin)
     * GET /api/admin/coupons
     */
    public function index(Request $request)
    {
        $query = Coupon::query();

        if ($request->has('search') && $request->search) {
            $query->where('code', 'like', '%' . $request->search . '%')
                  ->orWhere('name', 'like', '%' . $request->search . '%');
        }

        if ($request->has('is_active') && $request->is_active !== '') {
            $query->where('is_active', filter_var($request->is_active, FILTER_VALIDATE_BOOLEAN));
        }

        $coupons = $query->orderBy('created_at', 'desc')
            ->paginate($request->get('limit', 15));

        return response()->json([
            'success' => true,
            'data' => $coupons->items(),
            'pagination' => [
                'current_page' => $coupons->currentPage(),
                'per_page'     => $coupons->perPage(),
                'total'        => $coupons->total(),
                'last_page'    => $coupons->lastPage(),
            ],
        ]);
    }

    /**
     * Create a new coupon (admin)
     * POST /api/admin/coupons
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'code'                  => 'required|string|max:50|unique:coupons,code',
            'name'                  => 'required|string|max:255',
            'description'           => 'nullable|string|max:1000',
            'type'                  => 'required|in:fixed,percentage',
            'value'                 => 'required|numeric|min:0',
            'minimum_amount'        => 'nullable|numeric|min:0',
            'maximum_discount'      => 'nullable|numeric|min:0',
            'usage_limit'           => 'nullable|integer|min:1',
            'usage_limit_per_user'  => 'nullable|integer|min:1',
            'is_active'             => 'boolean',
            'starts_at'             => 'nullable|date',
            'expires_at'            => 'nullable|date|after_or_equal:starts_at',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors'  => $validator->errors(),
            ], 422);
        }

        $data = $validator->validated();
        $data['code'] = strtoupper($data['code']);

        $coupon = Coupon::create($data);

        return response()->json([
            'success' => true,
            'message' => 'Coupon created successfully',
            'data'    => $coupon,
        ], 201);
    }

    /**
     * Get a specific coupon (admin)
     * GET /api/admin/coupons/{id}
     */
    public function show($id)
    {
        $coupon = Coupon::find($id);

        if (!$coupon) {
            return response()->json([
                'success' => false,
                'message' => 'Coupon not found',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data'    => $coupon,
        ]);
    }

    /**
     * Update a coupon (admin)
     * PUT /api/admin/coupons/{id}
     */
    public function update(Request $request, $id)
    {
        $coupon = Coupon::find($id);

        if (!$coupon) {
            return response()->json([
                'success' => false,
                'message' => 'Coupon not found',
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'code'                  => 'string|max:50|unique:coupons,code,' . $id,
            'name'                  => 'string|max:255',
            'description'           => 'nullable|string|max:1000',
            'type'                  => 'in:fixed,percentage',
            'value'                 => 'numeric|min:0',
            'minimum_amount'        => 'nullable|numeric|min:0',
            'maximum_discount'      => 'nullable|numeric|min:0',
            'usage_limit'           => 'nullable|integer|min:1',
            'usage_limit_per_user'  => 'nullable|integer|min:1',
            'is_active'             => 'boolean',
            'starts_at'             => 'nullable|date',
            'expires_at'            => 'nullable|date',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors'  => $validator->errors(),
            ], 422);
        }

        $data = $validator->validated();
        if (isset($data['code'])) {
            $data['code'] = strtoupper($data['code']);
        }

        $coupon->update($data);

        return response()->json([
            'success' => true,
            'message' => 'Coupon updated successfully',
            'data'    => $coupon,
        ]);
    }

    /**
     * Delete a coupon (admin)
     * DELETE /api/admin/coupons/{id}
     */
    public function destroy($id)
    {
        $coupon = Coupon::find($id);

        if (!$coupon) {
            return response()->json([
                'success' => false,
                'message' => 'Coupon not found',
            ], 404);
        }

        $coupon->delete();

        return response()->json([
            'success' => true,
            'message' => 'Coupon deleted successfully',
        ]);
    }
}
