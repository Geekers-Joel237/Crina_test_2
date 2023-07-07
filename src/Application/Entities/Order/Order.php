<?php

namespace App\Application\Entities\Order;

use App\Application\Enums\OrderAction;
use App\Application\Enums\OrderStatus;
use App\Application\ValueObjects\Id;
use App\Application\ValueObjects\OrderElement;

class Order
{

    /**
     * @var OrderElement[]
     */
    private array $orderElements;
    private OrderStatus $status;

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
        ?Id          $id = null
    ): self
    {
        $self = new self($id ?? new Id(time()));
        $self->addElementToOrder($orderElement);
        $self->changeStatus(OrderStatus::IS_SAVED);

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

    /**
     * @return OrderElement[]
     */
    public function orderElements(): array
    {
        return $this->orderElements;
    }

    private function removeElementFromOrder(OrderElement $orderElement): void
    {
        $this->orderElements = array_values(array_filter(
            $this->orderElements,
            fn(OrderElement $e) => $e->reference()->value() !== $orderElement->reference()->value()
        ));
        if (count($this->orderElements) === 0) {
            $this->changeStatus(OrderStatus::IS_DESTROYED);
        }
    }

    public function changeElements(
        OrderElement $orderElement,
        OrderAction  $action
    ): void
    {
        if ($action === OrderAction::ADD_TO_ORDER) {
            $this->addElementToOrder($orderElement);
            return;
        }
        $this->removeElementFromOrder($orderElement);
    }

    public function status(): OrderStatus
    {
        return $this->status;
    }

    private function changeStatus(OrderStatus $status): void
    {
        $this->status = $status;
    }
}