<?php

namespace App\Application\Entities\Fruit;

use App\Application\ValueObjects\FruitReference;
use App\Application\ValueObjects\Id;

class Fruit
{

    public function __construct(
        private Id             $id,
        private FruitReference $reference
    )
    {
    }

    public static function create(Id $id, FruitReference $reference): self
    {
        return new self($id, $reference);
    }

    /**
     * @return FruitReference
     */
    public function reference(): FruitReference
    {
        return $this->reference;
    }

    public function id(): Id
    {
        return  $this->id;
    }
}