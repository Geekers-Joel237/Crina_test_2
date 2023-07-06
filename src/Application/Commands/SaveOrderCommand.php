<?php

namespace App\Application\Commands;

class SaveOrderCommand
{

    public ?string $orderId;

    /**
     * @param string $fruitRef
     * @param int $orderedQuantity
     */
    public function __construct(
        readonly public string $fruitRef,
        readonly public int $orderedQuantity
    )
    {
        $this->orderId = null;
    }
}