<?php

namespace App\Application\Commands;

use App\Application\Entities\Order\Order;

readonly class ValidateOrderCommand
{
    public function __construct(
        private Order $order
    )
    {
    }

    public function order(): Order
    {
        return $this->order;
    }
}