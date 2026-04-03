<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Coupon extends Model
{
    protected $fillable = [
        'code',
        'name',
        'description',
        'type',
        'value',
        'minimum_amount',
        'maximum_discount',
        'usage_limit',
        'usage_limit_per_user',
        'used_count',
        'is_active',
        'starts_at',
        'expires_at',
    ];

    protected $casts = [
        'value' => 'decimal:2',
        'minimum_amount' => 'decimal:2',
        'maximum_discount' => 'decimal:2',
        'is_active' => 'boolean',
        'starts_at' => 'datetime',
        'expires_at' => 'datetime',
    ];

    /**
     * Check if coupon is valid and calculate discount for given amount
     */
    public function calculateDiscount(float $subtotal): float
    {
        if ($this->type === 'fixed') {
            return min((float) $this->value, $subtotal);
        }

        // percentage
        $discount = $subtotal * ($this->value / 100);

        if ($this->maximum_discount !== null) {
            $discount = min($discount, (float) $this->maximum_discount);
        }

        return round($discount, 2);
    }

    /**
     * Check if this coupon is currently usable
     */
    public function isUsable(?int $userId = null): bool
    {
        if (!$this->is_active) {
            return false;
        }

        $now = now();

        if ($this->starts_at && $now->lt($this->starts_at)) {
            return false;
        }

        if ($this->expires_at && $now->gt($this->expires_at)) {
            return false;
        }

        if ($this->usage_limit !== null && $this->used_count >= $this->usage_limit) {
            return false;
        }

        if ($userId && $this->usage_limit_per_user !== null) {
            $userUsage = Order::where('user_id', $userId)
                ->where('coupon_code', $this->code)
                ->whereNotIn('status', ['cancelled'])
                ->count();

            if ($userUsage >= $this->usage_limit_per_user) {
                return false;
            }
        }

        return true;
    }
}
