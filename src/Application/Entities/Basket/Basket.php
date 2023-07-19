<?php

namespace App\Application\Entities\Basket;

use App\Application\Enums\BasketAction;
use App\Application\Enums\BasketStatus;
use App\Application\Exceptions\NotFoundOrderElementException;
use App\Application\ValueObjects\Id;
use App\Application\ValueObjects\OrderElement;

class Basket
{

    /**
     * @var OrderElement[]
     */
    private array $orderElements;
    private BasketStatus $status;

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
        $self->addElementToBasket($orderElement);
        $self->changeStatus(BasketStatus::IS_SAVED);

        return $self;
    }

    private function addElementToBasket(OrderElement $orderElement): void
    {
        $this->orderElements[] = $orderElement;
    }

    private function changeStatus(BasketStatus $status): void
    {
        $this->status = $status;
    }

    /**
     * @return Id|null
     */
    public function id(): ?Id
    {
        return $this->id;
    }

    /**
     * @return OrderElement[]
     */
    public function orderElements(): array
    {
        return $this->orderElements;
    }

    public function status(): BasketStatus
    {
        return $this->status;
    }

    /**
     * @throws NotFoundOrderElementException
     */
    public function updateBasket(
        OrderElement $orderElement,
        BasketAction $action
    ): void
    {
        if ($action === BasketAction::ADD_TO_BASKET) {
            if ($this->checkIfOrderElementExist($orderElement)) {
                $this->increaseElementQuantityInBasket($orderElement);
                return;
            }
            $this->addElementToBasket($orderElement);
            return;
        }

        $this->checkIfOrderElementAlreadyExistInBasketOrThrowNotFoundOrderElementException($orderElement);
        if ($action === BasketAction::MODIFY_THE_QUANTITY){
            $this->changeElementQuantityInBasket($orderElement);
            return;
        }
        $this->removeElementFromBasket($orderElement);
    }

    private function checkIfOrderElementExist(OrderElement $orderElement): bool
    {
        $retrieveOrderElement = array_values(
            array_filter(
                $this->orderElements, fn(OrderElement $element) => $element->reference()->value() === $orderElement->reference()->value()
            )
        );
        if (!empty($retrieveOrderElement)) {
            return true;
        }
        return false;
    }

    private function increaseElementQuantityInBasket(OrderElement $orderElement): void
    {
        $existingOrderElement =
            array_filter(
                $this->orderElements,
                fn(OrderElement $element) => $element->reference()->value() === $orderElement->reference()->value()
            );
        $key = key($existingOrderElement);
        $existingOrderElement[0]->orderedQuantity()->changeQuantity($orderElement->orderedQuantity()->value());
        $this->orderElements[$key] = $existingOrderElement[0];
    }

    /**
     * @throws NotFoundOrderElementException
     */
    private function checkIfOrderElementAlreadyExistInBasketOrThrowNotFoundOrderElementException(OrderElement $orderElement): void
    {
        $retrieveOrderElement = array_values(
            array_filter(
                $this->orderElements, fn(OrderElement $element) => $element->reference()->value() === $orderElement->reference()->value()
            )
        );

        if (empty($retrieveOrderElement)) {
            throw new NotFoundOrderElementException();
        }
    }

    private function removeElementFromBasket(OrderElement $orderElement): void
    {
        $this->orderElements = array_values(array_filter(
            $this->orderElements,
            fn(OrderElement $e) => $e->reference()->value() !== $orderElement->reference()->value()
        ));
        if (count($this->orderElements) === 0) {
            $this->changeStatus(BasketStatus::IS_DESTROYED);
        }
    }

    public function setIsValidated(): void
    {
        $this->status = BasketStatus::IS_VALIDATED;
    }

    private function changeElementQuantityInBasket(OrderElement $orderElement): void
    {
        $existingOrderElement =
            array_filter(
                $this->orderElements,
                fn(OrderElement $element) => $element->reference()->value() === $orderElement->reference()->value()
            );
        $key = key($existingOrderElement);
        $existingOrderElement[0]->orderedQuantity()->changeQuantity(
            $orderElement->orderedQuantity()->value() -
            $existingOrderElement[0]->orderedQuantity()->value()
        );
        $this->orderElements[$key] = $existingOrderElement[0];
    }


}