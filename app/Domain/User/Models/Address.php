<?php

namespace App\Domain\User\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Address extends Model
{
    protected $fillable = [
        'customer_id',
        'contact_name',
        'contact_phone',
        'province',
        'city',
        'district',
        'address',
        'longitude',
        'latitude',
        'is_default',
    ];

    protected function casts(): array
    {
        return [
            'longitude' => 'decimal:7',
            'latitude' => 'decimal:7',
            'is_default' => 'boolean',
        ];
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function getFullAddress(): string
    {
        return $this->province . $this->city . $this->district . $this->address;
    }
}
