<?php

namespace App\Application\Entities\Fruit;

use App\Application\ValueObjects\FruitReference;
use App\Application\ValueObjects\Id;

interface FruitRepository
{
    /**
     * @param FruitReference $fruitRef
     * @return Fruit|null
     */
    public function byReference(FruitReference $fruitRef): ?Fruit;

    public function delete(Id $fruitId);
}