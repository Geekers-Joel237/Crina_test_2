<?php

namespace App\Persistence\Repositories\Basket;

use App\Application\Entities\Basket\Basket;
use App\Application\Entities\Basket\BasketRepository;
use App\Application\ValueObjects\Id;

class InMemoryBasketRepository implements BasketRepository
{

    private array $baskets = [];

    public function save(Basket $basket): void
    {
        $this->baskets[] = $basket;
    }

    public function byId(Id $basketId): ?Basket
    {
        $result = array_values(array_filter(
                $this->baskets, fn(Basket $b) => $b->id()->value() === $basketId->value())
        );

        return count($result) > 0 ? $result[0] : null;
    }
}