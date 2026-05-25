<?php

namespace App\Infrastructure\Persistence\Repositories;

use App\Domain\User\Models\Customer;
use App\Domain\User\Repositories\CustomerRepositoryInterface;

class CustomerRepository implements CustomerRepositoryInterface
{
    public function findById(int $id): ?Customer
    {
        return Customer::with('addresses')->find($id);
    }

    public function findByUserId(int $userId): ?Customer
    {
        return Customer::with('addresses')->where('user_id', $userId)->first();
    }

    public function create(array $data): Customer
    {
        return Customer::create($data);
    }

    public function update(Customer $customer, array $data): Customer
    {
        $customer->update($data);
        return $customer->fresh();
    }
}
