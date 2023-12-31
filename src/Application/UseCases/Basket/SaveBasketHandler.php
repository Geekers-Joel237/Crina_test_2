<?php

namespace App\Application\UseCases\Basket;

use App\Application\Commands\SaveBasketCommand;
use App\Application\Entities\Basket\Basket;
use App\Application\Entities\Basket\BasketRepository;
use App\Application\Enums\BasketAction;
use App\Application\Exceptions\NotAvailableInStockFruitReferenceException;
use App\Application\Exceptions\NotFoundBasketException;
use App\Application\Exceptions\NotFoundFruitReferenceException;
use App\Application\Exceptions\NotFoundOrderElementException;
use App\Application\Responses\SaveBasketResponse;
use App\Application\Services\CheckFruitReferenceAvailabilityService;
use App\Application\Services\GetFruitByReferenceService;
use App\Application\ValueObjects\FruitReference;
use App\Application\ValueObjects\Id;
use App\Application\ValueObjects\OrderedQuantity;
use App\Application\ValueObjects\OrderElement;

readonly class SaveBasketHandler
{


    public function __construct(
        private BasketRepository                       $repository,
        private GetFruitByReferenceService             $verifyIfFruitReferenceExistsOrThrowNotFoundException,
        private CheckFruitReferenceAvailabilityService $verifyIfFruitReferenceIsAvailableInStockOrThrowNotAvailableInStockException,

    )
    {
    }

    /**
     * @throws NotFoundBasketException
     * @throws NotFoundFruitReferenceException|NotFoundOrderElementException
     * @throws NotAvailableInStockFruitReferenceException
     */
    public function handle(SaveBasketCommand $command): SaveBasketResponse
    {
        $response = new SaveBasketResponse();

        $basketId = $command->basketId ? new Id($command->basketId) : null;
        $fruitRef = new FruitReference($command->fruitRef);

        $this->verifyIfFruitReferenceExistsOrThrowNotFoundException->execute($fruitRef);

        $orderedQuantity = $command->orderedQuantity ? new OrderedQuantity($command->orderedQuantity) : null;
        $orderElement = new OrderElement(
            reference: $fruitRef,
            orderedQuantity: $orderedQuantity
        );
        $this->verifyIfFruitReferenceIsAvailableInStockOrThrowNotAvailableInStockException->execute($orderElement);

        $existingBasket = $this->getBasketOrThrowNotFoundException($basketId);
        $action = BasketAction::in($command->action);

        $basket = Basket::create(
            orderElement: $orderElement,
            action: $action,
            existingBasket: $existingBasket,
            id: $basketId
        );
        $this->repository->save($basket);

        $response->isSaved = true;
        $response->basketId = $basket->id()->value();
        $response->basketStatus = $basket->status()->value;

        return $response;
    }


    /**
     * @param Id|null $orderId
     * @return Basket|null
     * @throws NotFoundBasketException
     */
    private function getBasketOrThrowNotFoundException(?Id $orderId): ?Basket
    {
        if (!$orderId){
            return null;
        }
        $order = $this->repository->byId($orderId);
        if (!$order) {
            throw new NotFoundBasketException("Cette commande n'existe pas !");
        }

        return $order;
    }
}