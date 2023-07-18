<?php

namespace App\Application\ValueObjects;

readonly class OrderElement
{

    public function __construct(
        private FruitReference   $reference,
        private ?OrderedQuantity $orderedQuantity = null,
    )
    {
    }

    public function reference(): FruitReference
    {
        return $this->reference;
    }

    public function orderedQuantity(): ?OrderedQuantity
    {
        return $this->orderedQuantity;
    }

}