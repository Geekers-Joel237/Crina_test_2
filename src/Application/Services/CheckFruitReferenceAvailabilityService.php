<?php

namespace App\Application\Services;

use App\Application\Entities\Fruit\FruitRepository;
use App\Application\Exceptions\FruitReferenceIsNotAvailableInStockException;
use App\Application\ValueObjects\OrderElement;

readonly class CheckFruitReferenceAvailabilityService
{
    private int $MINIMAL_ACCEPTABLE_QUANTITY;
    public function __construct(
        private FruitRepository $fruitRepository,
    )
    {
        $this->MINIMAL_ACCEPTABLE_QUANTITY = 5;
    }

    /**
     * @throws FruitReferenceIsNotAvailableInStockException
     */
    public function execute(OrderElement $orderElement): void
    {
        $fruitsByReference = $this->fruitRepository->allByReference($orderElement->reference());
        if (count($fruitsByReference) < $this->MINIMAL_ACCEPTABLE_QUANTITY) {
            throw new FruitReferenceIsNotAvailableInStockException("Ce fruit n'est plus disponible en stock !");
        }

        if ($orderElement->orderedQuantity()->value() >= ( count($fruitsByReference) - $this->MINIMAL_ACCEPTABLE_QUANTITY )){
            throw new FruitReferenceIsNotAvailableInStockException("La quantité demandée pour ce fruit n'est plus disponible en stock !");
        }
    }
}