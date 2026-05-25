<?php

namespace App\Http\Requests\Customer;

use Illuminate\Foundation\Http\FormRequest;

class CreateOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'merchant_id' => 'required|integer',
            'shop_id' => 'required|integer',
            'address_id' => 'required|integer',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|integer',
            'items.*.sku_id' => 'required|integer',
            'items.*.quantity' => 'required|integer|min:1',
            'remark' => 'nullable|string|max:500',
        ];
    }

    public function messages(): array
    {
        return [
            'merchant_id.required' => '商户ID不能为空',
            'shop_id.required' => '店铺ID不能为空',
            'address_id.required' => '地址ID不能为空',
            'items.required' => '商品列表不能为空',
            'items.*.product_id.required' => '商品ID不能为空',
            'items.*.sku_id.required' => 'SKU ID不能为空',
            'items.*.quantity.required' => '商品数量不能为空',
            'items.*.quantity.min' => '商品数量至少为1',
        ];
    }
}
