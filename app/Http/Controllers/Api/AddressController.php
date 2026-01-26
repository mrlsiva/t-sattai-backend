<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Address;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class AddressController extends Controller
{
    /**
     * Get all addresses for the authenticated user.
     */
    public function index(Request $request)
    {
        try {
            $addresses = $request->user()->addresses()
                ->orderBy('is_default', 'desc')
                ->orderBy('created_at', 'desc')
                ->get();

            return response()->json([
                'success' => true,
                'data' => $addresses->map(function ($address) {
                    return [
                        'id' => $address->id,
                        'type' => $address->type,
                        'first_name' => $address->first_name,
                        'last_name' => $address->last_name,
                        'full_name' => $address->full_name,
                        'company' => $address->company,
                        'address_line_1' => $address->address_line_1,
                        'address_line_2' => $address->address_line_2,
                        'city' => $address->city,
                        'state' => $address->state,
                        'postal_code' => $address->postal_code,
                        'country' => $address->country,
                        'phone' => $address->phone,
                        'is_default' => $address->is_default,
                        'formatted_address' => $address->formatted_address,
                        'created_at' => $address->created_at->toISOString(),
                        'updated_at' => $address->updated_at->toISOString(),
                    ];
                }),
                'message' => 'Addresses retrieved successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve addresses',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Store a new address for the authenticated user.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'type' => 'required|in:shipping,billing,both',
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'company' => 'nullable|string|max:255',
            'address_line_1' => 'required|string|max:255',
            'address_line_2' => 'nullable|string|max:255',
            'city' => 'required|string|max:100',
            'state' => 'required|string|max:100',
            'postal_code' => 'required|string|max:20',
            'country' => 'required|string|max:100',
            'phone' => 'nullable|string|max:20',
            'is_default' => 'boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $addressData = $validator->validated();
            $addressData['user_id'] = $request->user()->id;

            $address = Address::create($addressData);

            // If this is set as default, update other addresses
            if ($address->is_default) {
                $address->setAsDefault();
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'id' => $address->id,
                    'type' => $address->type,
                    'first_name' => $address->first_name,
                    'last_name' => $address->last_name,
                    'full_name' => $address->full_name,
                    'company' => $address->company,
                    'address_line_1' => $address->address_line_1,
                    'address_line_2' => $address->address_line_2,
                    'city' => $address->city,
                    'state' => $address->state,
                    'postal_code' => $address->postal_code,
                    'country' => $address->country,
                    'phone' => $address->phone,
                    'is_default' => $address->is_default,
                    'formatted_address' => $address->formatted_address,
                    'created_at' => $address->created_at->toISOString(),
                    'updated_at' => $address->updated_at->toISOString(),
                ],
                'message' => 'Address created successfully'
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create address',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified address.
     */
    public function show(Request $request, $id)
    {
        try {
            $address = $request->user()->addresses()->findOrFail($id);

            return response()->json([
                'success' => true,
                'data' => [
                    'id' => $address->id,
                    'type' => $address->type,
                    'first_name' => $address->first_name,
                    'last_name' => $address->last_name,
                    'full_name' => $address->full_name,
                    'company' => $address->company,
                    'address_line_1' => $address->address_line_1,
                    'address_line_2' => $address->address_line_2,
                    'city' => $address->city,
                    'state' => $address->state,
                    'postal_code' => $address->postal_code,
                    'country' => $address->country,
                    'phone' => $address->phone,
                    'is_default' => $address->is_default,
                    'formatted_address' => $address->formatted_address,
                    'created_at' => $address->created_at->toISOString(),
                    'updated_at' => $address->updated_at->toISOString(),
                ],
                'message' => 'Address retrieved successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Address not found',
                'error' => $e->getMessage()
            ], 404);
        }
    }

    /**
     * Update the specified address.
     */
    public function update(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'type' => 'sometimes|required|in:shipping,billing,both',
            'first_name' => 'sometimes|required|string|max:255',
            'last_name' => 'sometimes|required|string|max:255',
            'company' => 'nullable|string|max:255',
            'address_line_1' => 'sometimes|required|string|max:255',
            'address_line_2' => 'nullable|string|max:255',
            'city' => 'sometimes|required|string|max:100',
            'state' => 'sometimes|required|string|max:100',
            'postal_code' => 'sometimes|required|string|max:20',
            'country' => 'sometimes|required|string|max:100',
            'phone' => 'nullable|string|max:20',
            'is_default' => 'boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $address = $request->user()->addresses()->findOrFail($id);
            
            $address->update($validator->validated());

            // If this is set as default, update other addresses
            if ($request->has('is_default') && $request->is_default) {
                $address->setAsDefault();
            }

            $address->refresh();

            return response()->json([
                'success' => true,
                'data' => [
                    'id' => $address->id,
                    'type' => $address->type,
                    'first_name' => $address->first_name,
                    'last_name' => $address->last_name,
                    'full_name' => $address->full_name,
                    'company' => $address->company,
                    'address_line_1' => $address->address_line_1,
                    'address_line_2' => $address->address_line_2,
                    'city' => $address->city,
                    'state' => $address->state,
                    'postal_code' => $address->postal_code,
                    'country' => $address->country,
                    'phone' => $address->phone,
                    'is_default' => $address->is_default,
                    'formatted_address' => $address->formatted_address,
                    'created_at' => $address->created_at->toISOString(),
                    'updated_at' => $address->updated_at->toISOString(),
                ],
                'message' => 'Address updated successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Address not found or failed to update',
                'error' => $e->getMessage()
            ], 404);
        }
    }

    /**
     * Remove the specified address.
     */
    public function destroy(Request $request, $id)
    {
        try {
            $address = $request->user()->addresses()->findOrFail($id);
            
            $address->delete();

            return response()->json([
                'success' => true,
                'message' => 'Address deleted successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Address not found or failed to delete',
                'error' => $e->getMessage()
            ], 404);
        }
    }

    /**
     * Set an address as default.
     */
    public function setDefault(Request $request, $id)
    {
        try {
            $address = $request->user()->addresses()->findOrFail($id);
            
            $address->setAsDefault();

            return response()->json([
                'success' => true,
                'message' => 'Address set as default successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Address not found or failed to set as default',
                'error' => $e->getMessage()
            ], 404);
        }
    }

    /**
     * Get addresses by type (shipping/billing).
     */
    public function getByType(Request $request, $type)
    {
        $validator = Validator::make(['type' => $type], [
            'type' => 'required|in:shipping,billing,both'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid address type',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $addresses = $request->user()->addresses()
                ->where(function ($query) use ($type) {
                    $query->where('type', $type)
                          ->orWhere('type', 'both');
                })
                ->orderBy('is_default', 'desc')
                ->orderBy('created_at', 'desc')
                ->get();

            return response()->json([
                'success' => true,
                'data' => $addresses->map(function ($address) {
                    return [
                        'id' => $address->id,
                        'type' => $address->type,
                        'first_name' => $address->first_name,
                        'last_name' => $address->last_name,
                        'full_name' => $address->full_name,
                        'company' => $address->company,
                        'address_line_1' => $address->address_line_1,
                        'address_line_2' => $address->address_line_2,
                        'city' => $address->city,
                        'state' => $address->state,
                        'postal_code' => $address->postal_code,
                        'country' => $address->country,
                        'phone' => $address->phone,
                        'is_default' => $address->is_default,
                        'formatted_address' => $address->formatted_address,
                        'created_at' => $address->created_at->toISOString(),
                        'updated_at' => $address->updated_at->toISOString(),
                    ];
                }),
                'message' => "Addresses for type '{$type}' retrieved successfully"
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve addresses',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}