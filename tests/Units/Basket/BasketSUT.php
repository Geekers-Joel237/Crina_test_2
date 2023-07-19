<?php

namespace Tests\Units\Basket;

use App\Application\Entities\Basket\Basket;
use App\Application\Enums\BasketAction;
use App\Application\Exceptions\NotFoundOrderElementException;
use App\Application\ValueObjects\FruitReference;
use App\Application\ValueObjects\OrderedQuantity;
use App\Application\ValueObjects\OrderElement;

class BasketSUT
{
    private Basket $basket;

    /**
     * @throws NotFoundOrderElementException
     */
    public static function asBuilder(): self
    {
        $orderElement = new OrderElement(
            reference: new FruitReference('Ref01'),
            orderedQuantity: new OrderedQuantity(15)
        );

        $self = new self();
        $self->basket = Basket::create(
            $orderElement,
            BasketAction::ADD_TO_BASKET
        );
        return $self;
    }

    public function build(): Basket
    {
        return $this->basket;
    }

    /**
     * @throws NotFoundOrderElementException
     */
    public function withOtherElement(OrderElement $element): self
    {
        $this->basket->updateBasket($element, BasketAction::ADD_TO_BASKET);
        return $this;
    }
}