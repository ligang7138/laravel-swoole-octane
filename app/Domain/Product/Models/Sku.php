<?php

namespace App\Domain\Product\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Sku extends Model
{
    const STATUS_ON_SALE = 'on_sale';
    const STATUS_OFF_SALE = 'off_sale';

    protected $fillable = [
        'product_id',
        'name',
        'image',
        'price',
        'stock',
        'specs',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'price' => 'integer',
            'stock' => 'integer',
            'specs' => 'array',
        ];
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function isOnSale(): bool
    {
        return $this->status === self::STATUS_ON_SALE;
    }

    public function hasStock(int $quantity = 1): bool
    {
        return $this->stock >= $quantity;
    }

    public function decrementStock(int $quantity = 1): bool
    {
        return $this->where('id', $this->id)
            ->where('stock', '>=', $quantity)
            ->decrement('stock', $quantity) > 0;
    }

    public function incrementStock(int $quantity = 1): void
    {
        $this->increment('stock', $quantity);
    }
}
