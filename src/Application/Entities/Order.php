<?php

namespace App\Application\Entities;

use App\Application\ValueObjects\Id;
use App\Application\ValueObjects\OrderElement;

class Order
{

    /**
     * @var OrderElement[]
     */
    private array $orderElements;

    /**
     * @param Id $id
     */
    private function __construct(
        readonly private Id $id
    )
    {
        $this->orderElements = [];
    }

    public static function create(
        OrderElement $orderElement,
        ?Id $id = null
    ): self
    {
        $self = new self($id ?? new Id(time()));
        $self->addElementToOrder($orderElement);

        return $self;
    }

    /**
     * @return Id|null
     */
    public function id(): ?Id
    {
        return $this->id;
    }

    private function addElementToOrder(OrderElement $orderElement): void
    {
        $this->orderElements[] = $orderElement;
    }
}