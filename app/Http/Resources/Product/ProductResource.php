<?php

namespace App\Http\Resources\Product;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'description' => $this->description,
            'image' => $this->image,
            'price' => $this->price,
            'status' => $this->status,
            'sort' => $this->sort,
            'skus' => SkuResource::collection($this->whenLoaded('skus')),
            'created_at' => $this->created_at->toDateTimeString(),
        ];
    }
}
