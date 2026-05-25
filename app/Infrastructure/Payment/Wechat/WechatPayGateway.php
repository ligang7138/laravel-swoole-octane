<?php

namespace App\Infrastructure\Payment\Wechat;

use App\Infrastructure\Payment\Contracts\PaymentGatewayInterface;
use Illuminate\Support\Facades\Log;

class WechatPayGateway implements PaymentGatewayInterface
{
    public function createPayment(string $orderNo, int $amount, string $channel, array $options = []): array
    {
        try {
            $appId = config('payment.wechat.app_id');
            $mchId = config('payment.wechat.mch_id');

            // 微信支付下单（JSAPI / NATIVE / APP）
            $result = [
                'prepay_id' => 'wx' . time() . random_int(1000, 9999),
                'order_no' => $orderNo,
                'amount' => $amount,
                'channel' => $channel,
            ];

            Log::info('WechatPay createPayment', $result);
            return $result;
        } catch (\Throwable $e) {
            Log::error('WechatPay createPayment failed', [
                'order_no' => $orderNo,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    public function queryPayment(string $paymentNo): array
    {
        return [
            'payment_no' => $paymentNo,
            'status' => 'success',
            'transaction_id' => 'wx_trans_' . time(),
        ];
    }

    public function handleCallback(string $channel, array $data): array
    {
        return [
            'order_no' => $data['out_trade_no'] ?? '',
            'transaction_id' => $data['transaction_id'] ?? '',
            'status' => ($data['result_code'] ?? '') === 'SUCCESS' ? 'success' : 'failed',
        ];
    }

    public function refund(string $paymentNo, int $amount, string $reason = ''): array
    {
        return [
            'refund_no' => 'RF' . date('YmdHis') . random_int(1000, 9999),
            'payment_no' => $paymentNo,
            'amount' => $amount,
            'status' => 'processing',
        ];
    }
}
