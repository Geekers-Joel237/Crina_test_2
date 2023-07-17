<?php

namespace App\Application\Commands;

use App\Application\Enums\BasketAction;

class SaveBasketCommand
{

    public ?string $basketId;
    public ?int $action;

    /**
     * @param string $fruitRef
     * @param int|null $orderedQuantity
     */
    public function __construct(
        readonly public string $fruitRef,
        public ?int            $orderedQuantity = null
    )
    {
        $this->basketId = null;
        $this->action = BasketAction::ADD_TO_BASKET->value;
    }
}