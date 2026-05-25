<?php

namespace App\Domain\Order\Exceptions;

use Exception;

class OrderStateException extends Exception
{
    public static function cannotPay(string $status): self
    {
        return new self("订单状态为 [{$status}]，无法支付");
    }

    public static function cannotCancel(string $status): self
    {
        return new self("订单状态为 [{$status}]，无法取消");
    }

    public static function cannotDeliver(string $status): self
    {
        return new self("订单状态为 [{$status}]，无法配送");
    }

    public static function cannotRefund(string $status): self
    {
        return new self("订单状态为 [{$status}]，无法退款");
    }
}
