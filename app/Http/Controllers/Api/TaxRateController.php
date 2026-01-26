<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\TaxRate;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class TaxRateController extends Controller
{
    /**
     * Get all tax rates with filtering
     */
    public function index(Request $request)
    {
        try {
            $query = TaxRate::query();

            // Apply filters
            if ($request->has('country')) {
                $query->where('country', strtoupper($request->country));
            }

            if ($request->has('state')) {
                $query->where('state', strtoupper($request->state));
            }

            if ($request->has('is_active')) {
                $query->where('is_active', $request->boolean('is_active'));
            }

            if ($request->has('type')) {
                $query->where('type', $request->type);
            }

            // Order by priority and created date
            $taxRates = $query->orderBy('country')
                            ->orderBy('state')
                            ->orderBy('priority', 'desc')
                            ->orderBy('created_at', 'desc')
                            ->paginate($request->get('per_page', 50));

            return response()->json([
                'success' => true,
                'data' => $taxRates,
                'message' => 'Tax rates retrieved successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error retrieving tax rates: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get a specific tax rate
     */
    public function show($id)
    {
        try {
            $taxRate = TaxRate::findOrFail($id);

            return response()->json([
                'success' => true,
                'data' => $taxRate,
                'message' => 'Tax rate retrieved successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Tax rate not found'
            ], 404);
        }
    }

    /**
     * Create a new tax rate
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'country' => 'required|string|size:2',
            'state' => 'nullable|string|max:10',
            'city' => 'nullable|string|max:100',
            'postal_code' => 'nullable|string|max:20',
            'rate' => 'required|numeric|min:0|max:1',
            'type' => 'required|string|max:50',
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:500',
            'is_active' => 'boolean',
            'effective_from' => 'nullable|date',
            'effective_to' => 'nullable|date|after_or_equal:effective_from',
            'priority' => 'integer|min:0|max:999'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $data = $validator->validated();
            $data['country'] = strtoupper($data['country']);
            if (isset($data['state'])) {
                $data['state'] = strtoupper($data['state']);
            }

            $taxRate = TaxRate::create($data);

            return response()->json([
                'success' => true,
                'data' => $taxRate,
                'message' => 'Tax rate created successfully'
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error creating tax rate: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update a tax rate
     */
    public function update(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'country' => 'string|size:2',
            'state' => 'nullable|string|max:10',
            'city' => 'nullable|string|max:100',
            'postal_code' => 'nullable|string|max:20',
            'rate' => 'numeric|min:0|max:1',
            'type' => 'string|max:50',
            'name' => 'string|max:255',
            'description' => 'nullable|string|max:500',
            'is_active' => 'boolean',
            'effective_from' => 'nullable|date',
            'effective_to' => 'nullable|date|after_or_equal:effective_from',
            'priority' => 'integer|min:0|max:999'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $taxRate = TaxRate::findOrFail($id);
            
            $data = $validator->validated();
            if (isset($data['country'])) {
                $data['country'] = strtoupper($data['country']);
            }
            if (isset($data['state'])) {
                $data['state'] = strtoupper($data['state']);
            }

            $taxRate->update($data);

            return response()->json([
                'success' => true,
                'data' => $taxRate->fresh(),
                'message' => 'Tax rate updated successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error updating tax rate: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Delete a tax rate
     */
    public function destroy($id)
    {
        try {
            $taxRate = TaxRate::findOrFail($id);
            $taxRate->delete();

            return response()->json([
                'success' => true,
                'message' => 'Tax rate deleted successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error deleting tax rate: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Find tax rate for a specific location
     */
    public function findForLocation(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'country' => 'required|string|size:2',
            'state' => 'nullable|string|max:10',
            'city' => 'nullable|string|max:100',
            'postal_code' => 'nullable|string|max:20'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $taxRate = TaxRate::findForLocation(
                $request->country,
                $request->state,
                $request->city,
                $request->postal_code
            );

            if (!$taxRate) {
                return response()->json([
                    'success' => true,
                    'data' => [
                        'rate' => 0,
                        'description' => 'No tax applicable',
                        'location' => $request->only(['country', 'state', 'city', 'postal_code'])
                    ],
                    'message' => 'No tax rate found for location'
                ]);
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'id' => $taxRate->id,
                    'rate' => $taxRate->rate,
                    'rate_percentage' => $taxRate->rate_percentage,
                    'type' => $taxRate->type,
                    'name' => $taxRate->name,
                    'description' => $taxRate->description,
                    'location_display' => $taxRate->location_display,
                    'location' => $request->only(['country', 'state', 'city', 'postal_code']),
                    'is_currently_effective' => $taxRate->isCurrentlyEffective()
                ],
                'message' => 'Tax rate found for location'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error finding tax rate: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Calculate tax for a given amount and location
     */
    public function calculateTax(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'amount' => 'required|numeric|min:0',
            'country' => 'required|string|size:2',
            'state' => 'nullable|string|max:10',
            'city' => 'nullable|string|max:100',
            'postal_code' => 'nullable|string|max:20'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $amount = $request->amount;
            $taxRate = TaxRate::findForLocation(
                $request->country,
                $request->state,
                $request->city,
                $request->postal_code
            );

            if (!$taxRate) {
                return response()->json([
                    'success' => true,
                    'data' => [
                        'amount' => $amount,
                        'tax_amount' => 0,
                        'tax_rate' => 0,
                        'total_amount' => $amount,
                        'description' => 'No tax applicable',
                        'location' => $request->only(['country', 'state', 'city', 'postal_code'])
                    ],
                    'message' => 'Tax calculated (no tax applicable)'
                ]);
            }

            $taxAmount = $taxRate->calculateTax($amount);
            $totalAmount = $amount + $taxAmount;

            return response()->json([
                'success' => true,
                'data' => [
                    'amount' => $amount,
                    'tax_amount' => round($taxAmount, 2),
                    'tax_rate' => $taxRate->rate,
                    'tax_rate_percentage' => $taxRate->rate_percentage,
                    'total_amount' => round($totalAmount, 2),
                    'tax_type' => $taxRate->type,
                    'tax_name' => $taxRate->name,
                    'description' => $taxRate->description,
                    'location' => $request->only(['country', 'state', 'city', 'postal_code'])
                ],
                'message' => 'Tax calculated successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error calculating tax: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Bulk import tax rates
     */
    public function bulkImport(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'tax_rates' => 'required|array|min:1',
            'tax_rates.*.country' => 'required|string|size:2',
            'tax_rates.*.state' => 'nullable|string|max:10',
            'tax_rates.*.city' => 'nullable|string|max:100',
            'tax_rates.*.postal_code' => 'nullable|string|max:20',
            'tax_rates.*.rate' => 'required|numeric|min:0|max:1',
            'tax_rates.*.type' => 'required|string|max:50',
            'tax_rates.*.name' => 'required|string|max:255',
            'tax_rates.*.description' => 'nullable|string|max:500',
            'tax_rates.*.is_active' => 'boolean',
            'tax_rates.*.effective_from' => 'nullable|date',
            'tax_rates.*.effective_to' => 'nullable|date',
            'tax_rates.*.priority' => 'integer|min:0|max:999'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $imported = 0;
            $errors = [];

            foreach ($request->tax_rates as $index => $taxRateData) {
                try {
                    $taxRateData['country'] = strtoupper($taxRateData['country']);
                    if (isset($taxRateData['state'])) {
                        $taxRateData['state'] = strtoupper($taxRateData['state']);
                    }

                    TaxRate::create($taxRateData);
                    $imported++;
                } catch (\Exception $e) {
                    $errors[] = "Row {$index}: " . $e->getMessage();
                }
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'imported' => $imported,
                    'total' => count($request->tax_rates),
                    'errors' => $errors
                ],
                'message' => "Successfully imported {$imported} tax rates"
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error importing tax rates: ' . $e->getMessage()
            ], 500);
        }
    }
}
