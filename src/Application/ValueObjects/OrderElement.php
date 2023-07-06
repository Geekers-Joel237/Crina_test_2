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
}