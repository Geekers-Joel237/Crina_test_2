<?php

namespace App\Application\Commands;

readonly class ValidateBasketCommand
{
    public function __construct(
        private string $basketId,
        private int   $currency,
        private int   $meanPayment,

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

    public function meanPayment(): int
    {
        return $this->meanPayment;
    }
}