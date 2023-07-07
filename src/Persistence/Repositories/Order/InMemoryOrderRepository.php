<?php

namespace App\Persistence\Repositories\Order;

use App\Application\Entities\Order\Order;
use App\Application\Entities\Order\OrderRepository;
use App\Application\ValueObjects\Id;

class InMemoryOrderRepository implements OrderRepository
{

    private array $orders = [];

    public function save(Order $order): void
    {
        $this->orders[] = $order;
    }

    public function byId(Id $orderId): ?Order
    {
        $result = array_values(array_filter(
                $this->orders, fn(Order $o) => $o->id()->value() === $orderId->value())
        );

        return count($result) > 0 ? $result[0] : null;
    }
}