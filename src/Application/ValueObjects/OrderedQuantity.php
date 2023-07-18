<?php

namespace App\Application\ValueObjects;

use App\Application\Exceptions\InvalidCommandException;

class OrderedQuantity
{

    public function __construct(private int $value)
    {
        $this->validate();
    }

    /**
     * @return void
     * @throws InvalidCommandException
     */
    private function validate(): void
    {
        if ($this->value <= 0) {
            throw new InvalidCommandException("La quantité de fruits doit être supérieure à 0 !");
        }
    }

    public function value(): int
    {
        return $this->value;
    }

    public function changeQuantity(int $addQuantity): void
    {
        $this->value += $addQuantity;
    }
}