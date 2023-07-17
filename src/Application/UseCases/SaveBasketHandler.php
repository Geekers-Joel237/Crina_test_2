<?php

namespace App\Application\UseCases;

use App\Application\Commands\SaveBasketCommand;
use App\Application\Entities\Basket\Basket;
use App\Application\Entities\Basket\BasketRepository;
use App\Application\Enums\BasketAction;
use App\Application\Exceptions\InvalidCommandException;
use App\Application\Exceptions\NotAvailableInStockFruitReferenceException;
use App\Application\Exceptions\NotFoundFruitReferenceException;
use App\Application\Exceptions\NotFoundOrderElementException;
use App\Application\Exceptions\NotFoundBasketException;
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

        $orderedQuantity = new OrderedQuantity($command->orderedQuantity);
        $orderElement = new OrderElement(
            reference: $fruitRef,
            orderedQuantity: $orderedQuantity
        );
        $action = BasketAction::in($command->action);
        $this->verifyIfQuantityIsProvidedInAddToBasketCase($action, $orderedQuantity);
        $this->verifyIfFruitReferenceIsAvailableInStockOrThrowNotAvailableInStockException->execute($orderElement);

        if (!$basketId) {
            $basket = Basket::create(
                orderElement: $orderElement,
                id: $basketId
            );
        } else {
            $basket = $this->getBasketOrThrowNotFoundException($basketId);
            $basket->updateBasket($orderElement, $action);
        }

        $this->repository->save($basket);

        $response->isSaved = true;
        $response->basketId = $basket->id()->value();
        $response->basketStatus = $basket->status()->value;

        return $response;
    }

    /**
     * @param BasketAction $action
     * @param OrderedQuantity $orderedQuantity
     * @return void
     */
    public function verifyIfQuantityIsProvidedInAddToBasketCase(BasketAction $action, OrderedQuantity $orderedQuantity): void
    {
        if ($action === BasketAction::ADD_TO_BASKET) {
            if (is_null($orderedQuantity->value())) {
                throw new \InvalidArgumentException("Impossible d'ajouter sans préciser la quantité !");
            }
        }
    }

    /**
     * @param Id|null $orderId
     * @return Basket
     * @throws NotFoundBasketException
     */
    private function getBasketOrThrowNotFoundException(?Id $orderId): Basket
    {
        $order = $this->repository->byId($orderId);
        if (!$order) {
            throw new NotFoundBasketException("Cette commande n'existe pas !");
        }

        return $order;
    }
}