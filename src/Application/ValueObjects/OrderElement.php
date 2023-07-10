<?php

namespace App\Application\ValueObjects;

class OrderElement
{

    public function __construct(
        private readonly FruitReference $reference,
        private OrderedQuantity         $orderedQuantity
    )
    {
    }

    public function reference(): FruitReference
    {
        return $this->reference;
    }

    public function orderedQuantity(): OrderedQuantity
    {
        return  $this->orderedQuantity;
    }

    public function changeOrderedQuantity(OrderedQuantity $newQuantity): void
    {
        $this->orderedQuantity = $newQuantity;
    }
}