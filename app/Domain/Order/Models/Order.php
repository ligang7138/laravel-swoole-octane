<?php

namespace App\Domain\Order\Models;

use App\Domain\Order\Events\OrderCancelled;
use App\Domain\Order\Events\OrderCreated;
use App\Domain\Order\Events\OrderPaid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Order extends Model
{
    // 订单状态
    const STATUS_PENDING = 'pending';
    const STATUS_PAID = 'paid';
    const STATUS_DELIVERING = 'delivering';
    const STATUS_COMPLETED = 'completed';
    const STATUS_CANCELLED = 'cancelled';
    const STATUS_REFUNDING = 'refunding';
    const STATUS_REFUNDED = 'refunded';

    // 订单类型
    const TYPE_NORMAL = 'normal';
    const TYPE_GROUP = 'group';

    protected $fillable = [
        'order_no',
        'type',
        'status',
        'merchant_id',
        'shop_id',
        'customer_id',
        'address_id',
        'total_amount',
        'pay_amount',
        'discount_amount',
        'delivery_fee',
        'remark',
        'paid_at',
        'delivered_at',
        'completed_at',
        'cancelled_at',
        'cancel_reason',
    ];

    protected function casts(): array
    {
        return [
            'total_amount' => 'integer',
            'pay_amount' => 'integer',
            'discount_amount' => 'integer',
            'delivery_fee' => 'integer',
            'paid_at' => 'datetime',
            'delivered_at' => 'datetime',
            'completed_at' => 'datetime',
            'cancelled_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::created(function (Order $order) {
            event(new OrderCreated($order));
        });
    }

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function merchant(): BelongsTo
    {
        return $this->belongsTo(\App\Domain\Merchant\Models\Merchant::class);
    }

    public function shop(): BelongsTo
    {
        return $this->belongsTo(\App\Domain\Merchant\Models\Shop::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(\App\Domain\User\Models\Customer::class);
    }

    public function canPay(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    public function canCancel(): bool
    {
        return in_array($this->status, [self::STATUS_PENDING, self::STATUS_PAID]);
    }

    public function canDeliver(): bool
    {
        return $this->status === self::STATUS_PAID;
    }

    public function canComplete(): bool
    {
        return $this->status === self::STATUS_DELIVERING;
    }

    public function canRefund(): bool
    {
        return in_array($this->status, [self::STATUS_PAID, self::STATUS_DELIVERING]);
    }

    public function markAsPaid(): void
    {
        $this->update([
            'status' => self::STATUS_PAID,
            'paid_at' => now(),
        ]);
        event(new OrderPaid($this));
    }

    public function markAsDelivering(): void
    {
        $this->update([
            'status' => self::STATUS_DELIVERING,
            'delivered_at' => now(),
        ]);
    }

    public function markAsCompleted(): void
    {
        $this->update([
            'status' => self::STATUS_COMPLETED,
            'completed_at' => now(),
        ]);
    }

    public function markAsCancelled(string $reason = ''): void
    {
        $this->update([
            'status' => self::STATUS_CANCELLED,
            'cancelled_at' => now(),
            'cancel_reason' => $reason,
        ]);
        event(new OrderCancelled($this, $reason));
    }

    public static function generateOrderNo(): string
    {
        return date('YmdHis') . str_pad(random_int(0, 9999), 4, '0', STR_PAD_LEFT);
    }
}
