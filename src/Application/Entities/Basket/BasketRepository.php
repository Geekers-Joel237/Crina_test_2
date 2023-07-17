<?php

namespace App\Application\Entities\Basket;

use App\Application\ValueObjects\Id;

interface BasketRepository
{
    public function save(Basket $order): void;

    public function byId(Id $orderId): ?Basket;
}