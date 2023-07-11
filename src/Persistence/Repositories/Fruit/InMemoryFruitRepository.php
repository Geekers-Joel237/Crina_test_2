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
            Fruit::create(new Id('004'), new FruitReference('Ref01')),
            Fruit::create(new Id('005'), new FruitReference('Ref01')),
            Fruit::create(new Id('006'), new FruitReference('Ref01')),
            Fruit::create(new Id('007'), new FruitReference('Ref01')),
            Fruit::create(new Id('008'), new FruitReference('Ref01')),
            Fruit::create(new Id('009'), new FruitReference('Ref01')),
            Fruit::create(new Id('010'), new FruitReference('Ref01')),
            Fruit::create(new Id('011'), new FruitReference('Ref01')),
            Fruit::create(new Id('012'), new FruitReference('Ref01')),
            Fruit::create(new Id('013'), new FruitReference('Ref02')),
            Fruit::create(new Id('014'), new FruitReference('Ref02')),
            Fruit::create(new Id('015'), new FruitReference('Ref02')),
            Fruit::create(new Id('016'), new FruitReference('Ref02')),
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

    public function allByReference(FruitReference $fruitReference): array
    {
      return  array_values(array_filter(
            $this->fruits,
            fn(Fruit $f) => $f->reference()->value() === $fruitReference->value()
        ));
    }

    public function delete(Id $fruitId): void
    {
        $newFruits = array_values(array_filter(
            $this->fruits,
            fn(Fruit $f) => $f->id()->value() !== $fruitId->value()
        ));

        $this->fruits = $newFruits;
    }
}