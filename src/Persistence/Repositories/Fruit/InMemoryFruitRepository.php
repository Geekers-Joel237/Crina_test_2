<?php

namespace App\Persistence\Repositories\Fruit;

use App\Application\Entities\Fruit\Fruit;
use App\Application\Entities\Fruit\FruitRepository;
use App\Application\ValueObjects\FruitReference;
use App\Application\ValueObjects\Id;

class InMemoryFruitRepository implements FruitRepository
{

    /**
     * @var Fruit[]
     */
    public array $fruits = [];

    public function __construct()
    {
        $this->fruits = [
            Fruit::create(new Id('001'), new FruitReference('Ref01')),
            Fruit::create(new Id('002'), new FruitReference('Ref01')),
            Fruit::create(new Id('003'), new FruitReference('Ref01')),
        ];
    }

    public function byReference(FruitReference $fruitRef): ?Fruit
    {
        $result = array_values(array_filter(
            $this->fruits,
            fn(Fruit $f) => $f->reference()->value() === $fruitRef->value()
        ));

        return count($result) > 0 ? $result[0] : null;
    }
}