<?php

namespace App\Application\Commands;

readonly class ValidateBasketCommand
{
    public function __construct(
        private string $basketId,
        private int    $currency,
        private int    $paymentMethod,

    )
    {
    }

    public function basketId(): string
    {
        return $this->basketId;
    }

    public function currency(): int
    {
        return $this->currency;
    }

    public function paymentMethod(): int
    {
        return $this->paymentMethod;
    }
}