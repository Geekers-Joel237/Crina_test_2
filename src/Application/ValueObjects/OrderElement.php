<?php

namespace App\Application\ValueObjects;

class OrderElement
{

    public function __construct(
        private FruitReference $reference,
        private OrderedQuantity $orderedQuantity
    )
    {
    }

    public function reference(): FruitReference
    {
        return $this->reference;
    }
}