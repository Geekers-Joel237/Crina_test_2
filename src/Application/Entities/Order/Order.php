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
            $existingOrderElement = $this->checkIfOrderElementAlreadyExistInOrderBeforeKnowIfItsAddOrUpdateCase($orderElement);
            if ($existingOrderElement){
                $this->changeElementQuantityToOrder($existingOrderElement, $orderElement);
                return;
            }
            $this->addElementToOrder($orderElement);
            return;
        }

        //TODO : check if orderElement exist before remove it
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

    private function checkIfOrderElementAlreadyExistInOrderBeforeKnowIfItsAddOrUpdateCase(OrderElement $orderElement): ?OrderElement
    {
        $retrieveOrderElement =
            array_filter(
                $this->orderElements,fn(OrderElement $element)
                => $element->reference()->value() === $orderElement->reference()->value()
            );
        $key = key($retrieveOrderElement);
        return count($retrieveOrderElement) > 0 ? $retrieveOrderElement[$key] : null;
    }

    private function changeElementQuantityToOrder(OrderElement $existingOrderElement, OrderElement $orderElement): void
    {
       $existingOrderElement->orderedQuantity()->changeQuantity($orderElement->orderedQuantity()->value());
       $newOrder = array_values(
           array_filter(
               $this->orderElements,
               fn(OrderElement $element) => $element->reference()->value() !== $existingOrderElement->reference()->value()
           )
       );
       $this->orderElements = $newOrder;
       $this->orderElements[] = $existingOrderElement;
    }
}