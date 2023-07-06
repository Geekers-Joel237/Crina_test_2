<?php

namespace App\Persistence\Repositories;

use App\Application\Entities\Order;
use App\Application\Entities\OrderRepository;

class InMemoryOrderRepository implements OrderRepository
{

    public function save(Order $order): void
    {
        // TODO: Implement save() method.
    }
}