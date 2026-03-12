<?php

namespace App\Models;

use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Coupon extends Model
{
    /** @use HasFactory<\Database\Factories\CouponFactory> */
    use HasFactory, SoftDeletes;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'code',
        'type',
        'value',
        'starts_at',
        'expires_at',
        'is_active',
        'usage_limit',
        'times_used',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'value' => 'decimal:2',
            'starts_at' => 'datetime',
            'expires_at' => 'datetime',
            'is_active' => 'boolean',
            'usage_limit' => 'integer',
            'times_used' => 'integer',
        ];
    }

    public function carts(): HasMany
    {
        return $this->hasMany(Cart::class);
    }

    public function isCurrentlyValid(?CarbonInterface $at = null): bool
    {
        $now = $at ?? now();

        if (! $this->is_active) {
            return false;
        }

        if ($this->starts_at !== null && $now->lt($this->starts_at)) {
            return false;
        }

        if ($this->expires_at !== null && $now->gt($this->expires_at)) {
            return false;
        }

        if ($this->usage_limit !== null && $this->times_used >= $this->usage_limit) {
            return false;
        }

        return true;
    }

    public function calculateDiscountCents(int $subtotalCents): int
    {
        if ($subtotalCents <= 0) {
            return 0;
        }

        $discountCents = $this->type === 'percentage'
            ? (int) floor($subtotalCents * ((float) $this->value / 100))
            : (int) round((float) $this->value * 100);

        return max(0, min($subtotalCents, $discountCents));
    }
}
