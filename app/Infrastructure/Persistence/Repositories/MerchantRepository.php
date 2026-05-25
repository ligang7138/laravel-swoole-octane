<?php

namespace App\Infrastructure\Persistence\Repositories;

use App\Domain\Merchant\Models\Merchant;
use App\Domain\Merchant\Repositories\MerchantRepositoryInterface;

class MerchantRepository implements MerchantRepositoryInterface
{
    public function findById(int $id): ?Merchant
    {
        return Merchant::with('shops')->find($id);
    }

    public function create(array $data): Merchant
    {
        return Merchant::create($data);
    }

    public function update(Merchant $merchant, array $data): Merchant
    {
        $merchant->update($data);
        return $merchant->fresh();
    }

    public function paginate(int $perPage = 15)
    {
        return Merchant::with('shops')->orderBy('created_at', 'desc')->paginate($perPage);
    }

    public function findActiveById(int $id): ?Merchant
    {
        return Merchant::with('shops')
            ->where('id', $id)
            ->where('status', Merchant::STATUS_ACTIVE)
            ->first();
    }
}
