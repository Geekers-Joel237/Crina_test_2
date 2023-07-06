<?php

namespace App\Application\Commands;

readonly class SaveOrderCommand
{

    /**
     * @param string $fruitRef
     * @param int $orderedQuantity
     */
    public function __construct(
        public string $fruitRef,
        public int $orderedQuantity
    )
    {
    }
}