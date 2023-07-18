<?php

namespace App\Application\ValueObjects;

use App\Application\Exceptions\InvalidCommandException;

class FruitReference
{
    private float $unitPrice;

    /**
     * @param string $value
     * @throws InvalidCommandException
     */
    public function __construct(private readonly string $value)
    {
        $this->validate();
        $this->unitPrice = 500.0;
    }

    /**
     * @return void
     * @throws InvalidCommandException
     */
    private function validate(): void
    {
        if (empty($this->value)) {
            throw new InvalidCommandException("La référence est invalide !");
        }
    }

    public function value(): string
    {
        return $this->value;
    }

    public function unitPrice(): float
    {
        return $this->unitPrice;
    }

    public function changeUnitPrice(float $price): void
    {
        $this->unitPrice = $price;
    }
}