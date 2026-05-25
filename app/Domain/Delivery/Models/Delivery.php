<?php

namespace App\Domain\Delivery\Models;

use App\Domain\Delivery\Events\DeliveryAssigned;
use App\Domain\Delivery\Events\DeliveryCompleted;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Delivery extends Model
{
    const STATUS_PENDING = 'pending';
    const STATUS_ASSIGNED = 'assigned';
    const STATUS_PICKED_UP = 'picked_up';
    const STATUS_DELIVERING = 'delivering';
    const STATUS_COMPLETED = 'completed';
    const STATUS_FAILED = 'failed';

    protected $fillable = [
        'delivery_no',
        'order_id',
        'order_no',
        'merchant_id',
        'shop_id',
        'customer_id',
        'address_id',
        'rider_id',
        'status',
        'pickup_at',
        'completed_at',
        'failed_at',
        'fail_reason',
    ];

    protected function casts(): array
    {
        return [
            'pickup_at' => 'datetime',
            'completed_at' => 'datetime',
            'failed_at' => 'datetime',
        ];
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(\App\Domain\Order\Models\Order::class);
    }

    public function canAssign(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    public function canPickUp(): bool
    {
        return $this->status === self::STATUS_ASSIGNED;
    }

    public function canComplete(): bool
    {
        return $this->status === self::STATUS_DELIVERING;
    }

    public function assign(int $riderId): void
    {
        $this->update([
            'status' => self::STATUS_ASSIGNED,
            'rider_id' => $riderId,
        ]);
        event(new DeliveryAssigned($this));
    }

    public function markAsPickedUp(): void
    {
        $this->update([
            'status' => self::STATUS_PICKED_UP,
            'pickup_at' => now(),
        ]);
    }

    public function markAsDelivering(): void
    {
        $this->update(['status' => self::STATUS_DELIVERING]);
    }

    public function markAsCompleted(): void
    {
        $this->update([
            'status' => self::STATUS_COMPLETED,
            'completed_at' => now(),
        ]);
        event(new DeliveryCompleted($this));
    }

    public function markAsFailed(string $reason = ''): void
    {
        $this->update([
            'status' => self::STATUS_FAILED,
            'failed_at' => now(),
            'fail_reason' => $reason,
        ]);
    }

    public static function generateDeliveryNo(): string
    {
        return 'DLV' . date('YmdHis') . str_pad(random_int(0, 9999), 4, '0', STR_PAD_LEFT);
    }
}
