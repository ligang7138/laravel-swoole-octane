<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Http\Resources\Product\ProductResource;
use App\Services\Product\ProductService;

class ProductController extends Controller
{
    public function __construct(
        private readonly ProductService $productService,
    ) {}

    public function show(int $id)
    {
        $product = $this->productService->getProduct($id);
        if (!$product) {
            return response()->json(['message' => '商品不存在'], 404);
        }
        return new ProductResource($product);
    }

    public function shopProducts(int $shopId)
    {
        $products = $this->productService->getShopProducts($shopId);
        return ProductResource::collection($products);
    }
}
