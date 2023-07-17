<?php

namespace App\Application\Entities\Fruit;

use App\Application\Enums\FruitStatus;
use App\Application\ValueObjects\FruitReference;
use App\Application\ValueObjects\Id;

class Fruit
{

    public function __construct(
        private Id             $id,
        private FruitReference $reference,
        private FruitStatus    $fruitStatus,
    )
    {
    }

    public static function create(Id $id, FruitReference $reference): self
    {
        return new self($id, $reference,FruitStatus::AVAILABLE);
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

    public function status(): FruitStatus
    {
        return $this->fruitStatus;
    }
    public function setHasSold(): void
    {
        $this->fruitStatus = FruitStatus::SOLD;
    }

    public function setHasBusy(): void
    {
        $this->fruitStatus = FruitStatus::BUSY;
    }

    public function setHasAvailable(): void
    {
        $this->fruitStatus = FruitStatus::AVAILABLE;
    }
}