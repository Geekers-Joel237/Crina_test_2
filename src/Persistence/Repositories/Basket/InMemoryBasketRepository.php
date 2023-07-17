<?php

namespace App\Persistence\Repositories\Basket;

use App\Application\Entities\Basket\Basket;
use App\Application\Entities\Basket\BasketRepository;
use App\Application\ValueObjects\Id;

class InMemoryBasketRepository implements BasketRepository
{

    private array $orders = [];

    public function save(Basket $order): void
    {
        $this->orders[] = $order;
    }

    public function byId(Id $orderId): ?Basket
    {
        $result = array_values(array_filter(
                $this->orders, fn(Basket $o) => $o->id()->value() === $orderId->value())
        );

        return count($result) > 0 ? $result[0] : null;
    }
}