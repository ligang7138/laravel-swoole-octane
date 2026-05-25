<?php

namespace App\Infrastructure\Payment\Alipay;

use App\Infrastructure\Payment\Contracts\PaymentGatewayInterface;
use Illuminate\Support\Facades\Log;

class AlipayGateway implements PaymentGatewayInterface
{
    public function createPayment(string $orderNo, int $amount, string $channel, array $options = []): array
    {
        try {
            $appId = config('payment.alipay.app_id');

            $result = [
                'trade_no' => 'alipay' . time() . random_int(1000, 9999),
                'order_no' => $orderNo,
                'amount' => $amount,
                'channel' => $channel,
            ];

            Log::info('Alipay createPayment', $result);
            return $result;
        } catch (\Throwable $e) {
            Log::error('Alipay createPayment failed', [
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
            'transaction_id' => 'alipay_trans_' . time(),
        ];
    }

    public function handleCallback(string $channel, array $data): array
    {
        return [
            'order_no' => $data['out_trade_no'] ?? '',
            'transaction_id' => $data['trade_no'] ?? '',
            'status' => ($data['trade_status'] ?? '') === 'TRADE_SUCCESS' ? 'success' : 'failed',
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
