<?php

namespace App\Domain\Payment\Models;

use App\Domain\Payment\Events\PaymentFailed;
use App\Domain\Payment\Events\PaymentSucceeded;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Payment extends Model
{
    const STATUS_PENDING = 'pending';
    const STATUS_SUCCESS = 'success';
    const STATUS_FAILED = 'failed';
    const STATUS_REFUNDING = 'refunding';
    const STATUS_REFUNDED = 'refunded';

    const CHANNEL_WECHAT = 'wechat';
    const CHANNEL_ALIPAY = 'alipay';

    protected $fillable = [
        'payment_no',
        'order_id',
        'order_no',
        'merchant_id',
        'customer_id',
        'channel',
        'amount',
        'status',
        'transaction_id',
        'paid_at',
        'expired_at',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'integer',
            'paid_at' => 'datetime',
            'expired_at' => 'datetime',
        ];
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(\App\Domain\Order\Models\Order::class);
    }

    public function isSuccess(): bool
    {
        return $this->status === self::STATUS_SUCCESS;
    }

    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    public function markAsSuccess(string $transactionId): void
    {
        $this->update([
            'status' => self::STATUS_SUCCESS,
            'transaction_id' => $transactionId,
            'paid_at' => now(),
        ]);
        event(new PaymentSucceeded($this));
    }

    public function markAsFailed(): void
    {
        $this->update(['status' => self::STATUS_FAILED]);
        event(new PaymentFailed($this));
    }

    public static function generatePaymentNo(): string
    {
        return 'PAY' . date('YmdHis') . str_pad(random_int(0, 9999), 4, '0', STR_PAD_LEFT);
    }
}
