<?php

namespace App\Domain\User\Repositories;

use App\Domain\User\Models\Customer;

interface CustomerRepositoryInterface
{
    public function findById(int $id): ?Customer;

    public function findByUserId(int $userId): ?Customer;

    public function create(array $data): Customer;

    public function update(Customer $customer, array $data): Customer;
}
