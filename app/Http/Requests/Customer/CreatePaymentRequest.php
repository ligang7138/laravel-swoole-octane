<?php

namespace App\Http\Requests\Customer;

use Illuminate\Foundation\Http\FormRequest;

class CreatePaymentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'order_id' => 'required|integer',
            'channel' => 'required|string|in:wechat,alipay',
        ];
    }

    public function messages(): array
    {
        return [
            'order_id.required' => '订单ID不能为空',
            'channel.required' => '支付渠道不能为空',
            'channel.in' => '支付渠道仅支持 wechat 或 alipay',
        ];
    }
}
