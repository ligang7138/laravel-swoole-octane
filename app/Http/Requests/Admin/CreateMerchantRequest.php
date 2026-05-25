<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class CreateMerchantRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:200',
            'contact_name' => 'required|string|max:50',
            'contact_phone' => 'required|string|max:20',
            'business_license' => 'nullable|string|max:500',
            'commission_rate' => 'nullable|numeric|min:0|max:1',
        ];
    }
}
