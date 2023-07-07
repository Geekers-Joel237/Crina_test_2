<?php

namespace App\Application\Services;

use App\Application\Entities\Fruit\Fruit;
use App\Application\Entities\Fruit\FruitRepository;
use App\Application\Exceptions\NotFoundFruitReferenceException;
use App\Application\ValueObjects\FruitReference;

readonly class GetFruitByReferenceService
{

    public function __construct(private FruitRepository $repository)
    {
    }

    /**
     * @param FruitReference $fruitRef
     * @return Fruit
     * @throws NotFoundFruitReferenceException
     */
    public function execute(FruitReference $fruitRef): Fruit
    {
        $fruit = $this->repository->byReference($fruitRef);
        if (!$fruit) {
            throw new NotFoundFruitReferenceException("Aucun fruit avec la référence donnée !");
        }

        return $fruit;
    }
}