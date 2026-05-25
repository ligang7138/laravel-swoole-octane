<?php

namespace App\Http\Resources\Merchant;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MerchantResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'contact_name' => $this->contact_name,
            'contact_phone' => $this->contact_phone,
            'status' => $this->status,
            'commission_rate' => $this->commission_rate,
            'shops' => ShopResource::collection($this->whenLoaded('shops')),
            'created_at' => $this->created_at->toDateTimeString(),
        ];
    }
}
