<?php

namespace App\Services\Product;

use App\Domain\Product\Models\Product;
use App\Domain\Product\Repositories\ProductRepositoryInterface;
use Illuminate\Support\Facades\DB;

class ProductService
{
    public function __construct(
        private readonly ProductRepositoryInterface $productRepo,
    ) {}

    public function getProduct(int $id): ?Product
    {
        return $this->productRepo->findOnSaleById($id);
    }

    public function getShopProducts(int $shopId, int $perPage = 15)
    {
        return $this->productRepo->paginateByShopId($shopId, $perPage);
    }

    public function createProduct(array $data): Product
    {
        return DB::transaction(function () use ($data) {
            $skus = $data['skus'] ?? [];
            unset($data['skus']);

            $product = $this->productRepo->create($data);

            foreach ($skus as $sku) {
                $product->skus()->create($sku);
            }

            return $product->load('skus');
        });
    }

    public function updateProduct(int $id, array $data): Product
    {
        $product = $this->productRepo->findById($id);
        if (!$product) {
            throw new \RuntimeException('商品不存在');
        }

        return DB::transaction(function () use ($product, $data) {
            $skus = $data['skus'] ?? null;
            unset($data['skus']);

            $product = $this->productRepo->update($product, $data);

            if ($skus !== null) {
                $product->skus()->delete();
                foreach ($skus as $sku) {
                    $product->skus()->create($sku);
                }
            }

            return $product->fresh('skus');
        });
    }

    public function updateStatus(int $id, string $status): Product
    {
        $product = $this->productRepo->findById($id);
        if (!$product) {
            throw new \RuntimeException('商品不存在');
        }

        return $this->productRepo->update($product, ['status' => $status]);
    }
}
