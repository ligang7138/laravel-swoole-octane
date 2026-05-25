<?php

namespace App\Domain\Payment\Exceptions;

use Exception;

class PaymentException extends Exception
{
    public static function channelNotSupported(string $channel): self
    {
        return new self("支付渠道 [{$channel}] 不支持");
    }

    public static function paymentExpired(): self
    {
        return new self('支付已过期');
    }

    public static function amountMismatch(): self
    {
        return new self('支付金额不匹配');
    }
}
