<?php

namespace App\Http\Resources\Order;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OrderItemResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'product_id' => $this->product_id,
            'sku_id' => $this->sku_id,
            'product_name' => $this->product_name,
            'sku_name' => $this->sku_name,
            'image' => $this->image,
            'price' => $this->price,
            'quantity' => $this->quantity,
            'total_amount' => $this->total_amount,
        ];
    }
}
