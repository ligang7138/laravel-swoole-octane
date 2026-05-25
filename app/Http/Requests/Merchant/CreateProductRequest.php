<?php

namespace App\Http\Requests\Merchant;

use Illuminate\Foundation\Http\FormRequest;

class CreateProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:200',
            'description' => 'nullable|string|max:2000',
            'image' => 'nullable|string|max:500',
            'price' => 'required|integer|min:0',
            'category_id' => 'required|integer',
            'sort' => 'nullable|integer|min:0',
            'skus' => 'nullable|array',
            'skus.*.name' => 'required|string|max:200',
            'skus.*.price' => 'required|integer|min:0',
            'skus.*.stock' => 'required|integer|min:0',
            'skus.*.specs' => 'nullable|array',
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => '商品名称不能为空',
            'price.required' => '商品价格不能为空',
            'price.min' => '商品价格不能为负数',
            'category_id.required' => '分类ID不能为空',
        ];
    }
}
