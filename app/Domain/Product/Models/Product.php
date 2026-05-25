<?php

namespace App\Domain\Product\Models;

use App\Domain\Merchant\Models\Shop;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Product extends Model
{
    const STATUS_ON_SALE = 'on_sale';
    const STATUS_OFF_SALE = 'off_sale';

    protected $fillable = [
        'merchant_id',
        'shop_id',
        'category_id',
        'name',
        'description',
        'image',
        'price',
        'status',
        'sort',
    ];

    protected function casts(): array
    {
        return [
            'price' => 'integer',
            'sort' => 'integer',
        ];
    }

    public function shop(): BelongsTo
    {
        return $this->belongsTo(Shop::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function skus(): HasMany
    {
        return $this->hasMany(Sku::class);
    }

    public function isOnSale(): bool
    {
        return $this->status === self::STATUS_ON_SALE;
    }
}
