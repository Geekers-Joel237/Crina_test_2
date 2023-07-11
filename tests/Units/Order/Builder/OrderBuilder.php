<?php

namespace Tests\Units\Order\Builder;

use App\Application\Entities\Order\Order;
use App\Application\ValueObjects\OrderElement;

interface OrderBuilder
{
    public function build(): self;

    public function order(): Order;

    public function withOrderElement(OrderElement $orderElement): self;
}