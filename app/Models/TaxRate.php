<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class TaxRate extends Model
{
    use HasFactory;

    protected $fillable = [
        'country',
        'state',
        'city',
        'postal_code',
        'rate',
        'type',
        'name',
        'description',
        'is_active',
        'effective_from',
        'effective_to',
        'priority'
    ];

    protected $casts = [
        'rate' => 'decimal:6',
        'is_active' => 'boolean',
        'effective_from' => 'date',
        'effective_to' => 'date',
        'priority' => 'integer'
    ];

    /**
     * Scope to get active tax rates
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope to get effective tax rates for a given date
     */
    public function scopeEffectiveOn($query, $date = null)
    {
        $date = $date ?? Carbon::now()->toDateString();
        
        return $query->where(function ($q) use ($date) {
            $q->where('effective_from', '<=', $date)
              ->where(function ($subQ) use ($date) {
                  $subQ->whereNull('effective_to')
                       ->orWhere('effective_to', '>=', $date);
              });
        });
    }

    /**
     * Find the best matching tax rate for a location
     */
    public static function findForLocation($country, $state = null, $city = null, $postalCode = null)
    {
        // Start with base query for active and effective rates
        $query = static::active()->effectiveOn();
        
        // Build location matching conditions with priority
        $query->where('country', strtoupper($country));
        
        // Create subqueries for different specificity levels
        $exactMatch = clone $query;
        $stateMatch = clone $query;
        $countryMatch = clone $query;
        
        // 1. Try exact match (country + state + city + postal)
        if ($state && $city && $postalCode) {
            $exactMatch->where('state', strtoupper($state))
                      ->where('city', $city)
                      ->where('postal_code', $postalCode);
            
            $result = $exactMatch->orderBy('priority', 'desc')->first();
            if ($result) return $result;
        }
        
        // 2. Try state + city match
        if ($state && $city) {
            $stateMatch->where('state', strtoupper($state))
                      ->where('city', $city)
                      ->whereNull('postal_code');
            
            $result = $stateMatch->orderBy('priority', 'desc')->first();
            if ($result) return $result;
        }
        
        // 3. Try state match only
        if ($state) {
            $stateMatch = clone $query;
            $stateMatch->where('state', strtoupper($state))
                      ->whereNull('city')
                      ->whereNull('postal_code');
            
            $result = $stateMatch->orderBy('priority', 'desc')->first();
            if ($result) return $result;
        }
        
        // 4. Fall back to country-level rate
        $countryMatch->whereNull('state')
                    ->whereNull('city')
                    ->whereNull('postal_code');
        
        return $countryMatch->orderBy('priority', 'desc')->first();
    }

    /**
     * Get tax rate percentage as a formatted string
     */
    public function getRatePercentageAttribute()
    {
        return number_format($this->rate * 100, 2) . '%';
    }

    /**
     * Calculate tax amount for a given subtotal
     */
    public function calculateTax($subtotal)
    {
        return $subtotal * $this->rate;
    }

    /**
     * Get location display string
     */
    public function getLocationDisplayAttribute()
    {
        $parts = array_filter([
            $this->city,
            $this->state,
            $this->country
        ]);
        
        return implode(', ', $parts) ?: $this->country;
    }

    /**
     * Check if this tax rate is currently effective
     */
    public function isCurrentlyEffective()
    {
        $now = Carbon::now()->toDateString();
        
        $afterStart = !$this->effective_from || $this->effective_from <= $now;
        $beforeEnd = !$this->effective_to || $this->effective_to >= $now;
        
        return $this->is_active && $afterStart && $beforeEnd;
    }
}