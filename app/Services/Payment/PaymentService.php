<?php

namespace App\Services\Payment;

use App\Domain\Order\Exceptions\OrderStateException;
use App\Domain\Order\Repositories\OrderRepositoryInterface;
use App\Domain\Payment\Exceptions\PaymentException;
use App\Domain\Payment\Models\Payment;
use App\Domain\Payment\Repositories\PaymentRepositoryInterface;
use App\Infrastructure\Lock\Contracts\LockServiceInterface;
use App\Infrastructure\Messaging\Contracts\MessagePublisherInterface;
use App\Infrastructure\Payment\Contracts\PaymentGatewayInterface;
use Illuminate\Support\Facades\DB;

class PaymentService
{
    private array $gateways = [];

    public function __construct(
        private readonly PaymentRepositoryInterface $paymentRepo,
        private readonly OrderRepositoryInterface $orderRepo,
        private readonly LockServiceInterface $lockService,
        private readonly MessagePublisherInterface $messagePublisher,
    ) {}

    public function registerGateway(string $channel, PaymentGatewayInterface $gateway): void
    {
        $this->gateways[$channel] = $gateway;
    }

    public function createPayment(int $orderId, string $channel): Payment
    {
        return $this->lockService->runWithLock("payment:create:{$orderId}", 5, function () use ($orderId, $channel) {
            $order = $this->orderRepo->findById($orderId);

            if (!$order) {
                throw new \RuntimeException('订单不存在');
            }

            if (!$order->canPay()) {
                throw OrderStateException::cannotPay($order->status);
            }

            $existingPayment = $this->paymentRepo->findByOrderNo($order->order_no);
            if ($existingPayment && $existingPayment->isPending()) {
                return $existingPayment;
            }

            $gateway = $this->getGateway($channel);
            $result = $gateway->createPayment($order->order_no, $order->pay_amount, $channel);

            return $this->paymentRepo->create([
                'payment_no' => Payment::generatePaymentNo(),
                'order_id' => $order->id,
                'order_no' => $order->order_no,
                'merchant_id' => $order->merchant_id,
                'customer_id' => $order->customer_id,
                'channel' => $channel,
                'amount' => $order->pay_amount,
                'status' => Payment::STATUS_PENDING,
                'expired_at' => now()->addMinutes(30),
            ]);
        });
    }

    public function handleCallback(string $channel, array $data): Payment
    {
        $gateway = $this->getGateway($channel);
        $result = $gateway->handleCallback($channel, $data);

        $payment = $this->paymentRepo->findByOrderNo($result['order_no']);
        if (!$payment) {
            throw new \RuntimeException('支付记录不存在');
        }

        return $this->lockService->runWithLock("payment:callback:{$payment->id}", 5, function () use ($payment, $result) {
            return DB::transaction(function () use ($payment, $result) {
                if ($result['status'] === 'success') {
                    $payment->markAsSuccess($result['transaction_id']);

                    $order = $this->orderRepo->findByOrderNo($payment->order_no);
                    if ($order && $order->canPay()) {
                        $order->markAsPaid();
                    }
                } else {
                    $payment->markAsFailed();
                }

                $this->messagePublisher->publish('payment_result', json_encode([
                    'payment_no' => $payment->payment_no,
                    'order_no' => $payment->order_no,
                    'status' => $result['status'],
                ]));

                return $payment->fresh();
            });
        });
    }

    public function refund(int $paymentId, int $amount, string $reason = ''): array
    {
        $payment = $this->paymentRepo->findById($paymentId);
        if (!$payment || !$payment->isSuccess()) {
            throw PaymentException::amountMismatch();
        }

        $gateway = $this->getGateway($payment->channel);
        return $gateway->refund($payment->payment_no, $amount, $reason);
    }

    private function getGateway(string $channel): PaymentGatewayInterface
    {
        if (!isset($this->gateways[$channel])) {
            throw PaymentException::channelNotSupported($channel);
        }
        return $this->gateways[$channel];
    }
}
