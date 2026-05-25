<?php

namespace App\Domain\Product\Repositories;

use App\Domain\Product\Models\Product;

interface ProductRepositoryInterface
{
    public function findById(int $id): ?Product;

    public function create(array $data): Product;

    public function update(Product $product, array $data): Product;

    public function paginateByShopId(int $shopId, int $perPage = 15);

    public function findOnSaleById(int $id): ?Product;
}
