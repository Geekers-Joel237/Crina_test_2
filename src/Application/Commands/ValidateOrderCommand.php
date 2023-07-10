<?php

namespace App\Application\Commands;


readonly class ValidateOrderCommand
{
    public function __construct(
        private string $orderId,
        private int    $currency,
        private int    $meanPayment
    )
    {
    }

    public function orderId(): string
    {
        return $this->orderId;
    }

    public function currency() : int
    {
        return $this->currency;
    }

    public function meanPayment(): int
    {
        return $this->meanPayment;
    }
}