<?php

namespace App\Http\Controllers\Merchant;

use App\Http\Controllers\Controller;
use App\Http\Requests\Merchant\CreateProductRequest;
use App\Http\Requests\Merchant\UpdateProductRequest;
use App\Http\Resources\Product\ProductResource;
use App\Services\Product\ProductService;

class ProductController extends Controller
{
    public function __construct(
        private readonly ProductService $productService,
    ) {}

    public function index(int $shopId)
    {
        $products = $this->productService->getShopProducts($shopId);
        return ProductResource::collection($products);
    }

    public function store(CreateProductRequest $request)
    {
        $data = $request->validated();
        $data['merchant_id'] = $this->getMerchantId();
        $data['shop_id'] = $request->shop_id ?? 0;

        $product = $this->productService->createProduct($data);
        return (new ProductResource($product))->response()->setStatusCode(201);
    }

    public function show(int $id)
    {
        $product = $this->productService->getProduct($id);
        if (!$product) {
            return response()->json(['message' => '商品不存在'], 404);
        }
        return new ProductResource($product);
    }

    public function update(UpdateProductRequest $request, int $id)
    {
        $product = $this->productService->updateProduct($id, $request->validated());
        return new ProductResource($product);
    }

    public function updateStatus(int $id, string $status)
    {
        $product = $this->productService->updateStatus($id, $status);
        return new ProductResource($product);
    }

    private function getMerchantId(): int
    {
        return auth()->user()->merchant?->id ?? 0;
    }
}
