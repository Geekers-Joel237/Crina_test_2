<?php

namespace App\Application\Commands;

use App\Application\Enums\BasketAction;
use App\Application\Exceptions\InvalidCommandException;

class SaveBasketCommand
{

    public ?string $basketId;
    public ?int $orderedQuantity;

    /**
     * @param string $fruitRef
     * @param int $action
     */
    private function __construct(
        readonly public string $fruitRef,
        public int             $action,
    )
    {
        $this->action = BasketAction::in($action)->value;
        $this->basketId = null;
        $this->orderedQuantity = null;
    }

    public static function create(
        string  $fruitRef,
        int     $action,
        ?int    $orderedQuantity = null,
        ?string $basketId = null
    ): self
    {
        $self = new self($fruitRef, $action);
        if ($orderedQuantity) {
            $self->orderedQuantity = $orderedQuantity;
        }
        if ($basketId) {
            $self->basketId = $basketId;
        }
        $self->validate();
        return $self;
    }

    private function validate(): void
    {
        if ($this->action === BasketAction::ADD_TO_BASKET->value) {
            if (!$this->orderedQuantity) {
                throw new InvalidCommandException("Impossible d'ajouter sans préciser la quantité !");
            }
        }
    }
}