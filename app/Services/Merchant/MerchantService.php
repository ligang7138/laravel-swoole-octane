<?php

namespace App\Services\Merchant;

use App\Domain\Merchant\Models\Merchant;
use App\Domain\Merchant\Repositories\MerchantRepositoryInterface;

class MerchantService
{
    public function __construct(
        private readonly MerchantRepositoryInterface $merchantRepo,
    ) {}

    public function getMerchant(int $id): ?Merchant
    {
        return $this->merchantRepo->findById($id);
    }

    public function createMerchant(array $data): Merchant
    {
        return $this->merchantRepo->create($data);
    }

    public function updateMerchant(int $id, array $data): Merchant
    {
        $merchant = $this->merchantRepo->findById($id);
        if (!$merchant) {
            throw new \RuntimeException('商户不存在');
        }
        return $this->merchantRepo->update($merchant, $data);
    }

    public function listMerchants(int $perPage = 15)
    {
        return $this->merchantRepo->paginate($perPage);
    }

    public function activateMerchant(int $id): Merchant
    {
        return $this->updateMerchant($id, ['status' => Merchant::STATUS_ACTIVE]);
    }

    public function suspendMerchant(int $id): Merchant
    {
        return $this->updateMerchant($id, ['status' => Merchant::STATUS_SUSPENDED]);
    }
}
