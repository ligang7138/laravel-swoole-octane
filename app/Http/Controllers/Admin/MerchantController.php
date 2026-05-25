<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\CreateMerchantRequest;
use App\Http\Resources\Merchant\MerchantResource;
use App\Services\Merchant\MerchantService;

class MerchantController extends Controller
{
    public function __construct(
        private readonly MerchantService $merchantService,
    ) {}

    public function index()
    {
        $merchants = $this->merchantService->listMerchants();
        return MerchantResource::collection($merchants);
    }

    public function store(CreateMerchantRequest $request)
    {
        $merchant = $this->merchantService->createMerchant($request->validated());
        return (new MerchantResource($merchant))->response()->setStatusCode(201);
    }

    public function show(int $id)
    {
        $merchant = $this->merchantService->getMerchant($id);
        if (!$merchant) {
            return response()->json(['message' => '商户不存在'], 404);
        }
        return new MerchantResource($merchant);
    }

    public function activate(int $id)
    {
        $merchant = $this->merchantService->activateMerchant($id);
        return new MerchantResource($merchant);
    }

    public function suspend(int $id)
    {
        $merchant = $this->merchantService->suspendMerchant($id);
        return new MerchantResource($merchant);
    }
}
