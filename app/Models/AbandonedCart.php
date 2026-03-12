<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AbandonedCart extends Model
{
    /** @use HasFactory<\Database\Factories\AbandonedCartFactory> */
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'user_id',
        'status',
        'item_count',
        'subtotal',
        'items_snapshot',
        'last_activity_at',
        'reminder_coupon_id',
        'reminder_sent_at',
        'recovered_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'item_count' => 'integer',
            'subtotal' => 'decimal:2',
            'items_snapshot' => 'array',
            'last_activity_at' => 'datetime',
            'reminder_sent_at' => 'datetime',
            'recovered_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function reminderCoupon(): BelongsTo
    {
        return $this->belongsTo(Coupon::class, 'reminder_coupon_id');
    }
}
