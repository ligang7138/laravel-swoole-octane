<?php

namespace App\Infrastructure\Persistence\Repositories;

use App\Domain\Product\Models\Product;
use App\Domain\Product\Repositories\ProductRepositoryInterface;

class ProductRepository implements ProductRepositoryInterface
{
    public function findById(int $id): ?Product
    {
        return Product::with(['skus', 'category', 'shop'])->find($id);
    }

    public function create(array $data): Product
    {
        return Product::create($data);
    }

    public function update(Product $product, array $data): Product
    {
        $product->update($data);
        return $product->fresh();
    }

    public function paginateByShopId(int $shopId, int $perPage = 15)
    {
        return Product::with(['skus', 'category'])
            ->where('shop_id', $shopId)
            ->orderBy('sort')
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);
    }

    public function findOnSaleById(int $id): ?Product
    {
        return Product::with(['skus' => function ($query) {
            $query->where('status', 'on_sale');
        }, 'category', 'shop'])
            ->where('id', $id)
            ->where('status', Product::STATUS_ON_SALE)
            ->first();
    }
}
