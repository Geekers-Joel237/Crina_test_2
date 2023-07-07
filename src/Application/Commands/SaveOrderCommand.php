<?php

namespace App\Application\Commands;

use App\Application\Enums\OrderAction;

class SaveOrderCommand
{

    public ?string $orderId;
    public ?int $action;

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
        $this->action = OrderAction::ADD_TO_ORDER->value;
    }
}