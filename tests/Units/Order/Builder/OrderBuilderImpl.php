<?php

namespace Tests\Units\Order\Builder;

use App\Application\Entities\Order\Order;
use App\Application\Enums\OrderAction;
use App\Application\Exceptions\NotFoundOrderElementException;
use App\Application\ValueObjects\FruitReference;
use App\Application\ValueObjects\OrderedQuantity;
use App\Application\ValueObjects\OrderElement;

class OrderBuilderImpl implements OrderBuilder
{
    private Order $order;

    public function build(): OrderBuilder
    {
        $orderElement = new OrderElement(
            reference: new FruitReference('Ref01'),
            orderedQuantity: new OrderedQuantity(10)
        );
        $this->order = Order::create(
            $orderElement
        );

        return $this;
    }

    public function order(): Order
    {
        return $this->order;
    }

    /**
     * @throws NotFoundOrderElementException
     */
    public function withOrderElement(OrderElement $orderElement): OrderBuilder
    {
        $this->order->updateOrder($orderElement,OrderAction::ADD_TO_ORDER);
        return $this;
    }
}