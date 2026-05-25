<?php

namespace App\Http\Requests\Merchant;

use Illuminate\Foundation\Http\FormRequest;

class UpdateProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => 'sometimes|string|max:200',
            'description' => 'nullable|string|max:2000',
            'image' => 'nullable|string|max:500',
            'price' => 'sometimes|integer|min:0',
            'category_id' => 'sometimes|integer',
            'status' => 'sometimes|string|in:on_sale,off_sale',
            'sort' => 'nullable|integer|min:0',
            'skus' => 'nullable|array',
            'skus.*.name' => 'required_with:skus|string|max:200',
            'skus.*.price' => 'required_with:skus|integer|min:0',
            'skus.*.stock' => 'required_with:skus|integer|min:0',
            'skus.*.specs' => 'nullable|array',
        ];
    }
}
