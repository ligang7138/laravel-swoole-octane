<?php

namespace App\Domain\Merchant\Repositories;

use App\Domain\Merchant\Models\Merchant;

interface MerchantRepositoryInterface
{
    public function findById(int $id): ?Merchant;

    public function create(array $data): Merchant;

    public function update(Merchant $merchant, array $data): Merchant;

    public function paginate(int $perPage = 15);

    public function findActiveById(int $id): ?Merchant;
}
