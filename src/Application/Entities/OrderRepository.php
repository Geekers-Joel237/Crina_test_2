<?php

namespace App\Application\Entities;

use App\Application\ValueObjects\Id;

interface OrderRepository
{
    public function save(Order $order): void;

    public function byId(Id $orderId): ?Order;
}