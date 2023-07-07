<?php

namespace App\Application\UseCases;

use App\Application\Commands\SaveOrderCommand;
use App\Application\Entities\Order\Order;
use App\Application\Entities\Order\OrderRepository;
use App\Application\Exceptions\NotFoundFruitReferenceException;
use App\Application\Exceptions\NotFoundOrderException;
use App\Application\Responses\SaveOrderResponse;
use App\Application\Services\GetFruitByReferenceService;
use App\Application\ValueObjects\FruitReference;
use App\Application\ValueObjects\Id;
use App\Application\ValueObjects\OrderedQuantity;
use App\Application\ValueObjects\OrderElement;

readonly class SaveOrderHandler
{


    public function __construct(
        private OrderRepository $repository,
        private GetFruitByReferenceService $verifyIfFruitReferenceExistsOrThrowNotFoundException
    )
    {
    }

    /**
     * @throws NotFoundOrderException
     * @throws NotFoundFruitReferenceException
     */
    public function handle(SaveOrderCommand $command): SaveOrderResponse
    {
        $response = new SaveOrderResponse();

        $orderId = $command->orderId ? new Id($command->orderId) : null;
        $fruitRef = new FruitReference($command->fruitRef);
        $orderElement = new OrderElement(
            reference: $fruitRef,
            orderedQuantity: new OrderedQuantity($command->orderedQuantity)
        );

        $this->verifyIfOrderExistsOrThrowNotFoundException($orderId);
        $this->verifyIfFruitReferenceExistsOrThrowNotFoundException->execute($fruitRef);

        $order = Order::create(
            orderElement: $orderElement,
            id: $orderId
        );

        $this->repository->save($order);

        $response->isSaved = true;
        $response->orderId = $order->id()->value();

        return $response;
    }

    /**
     * @param Id|null $orderId
     * @return void
     * @throws NotFoundOrderException
     */
    private function verifyIfOrderExistsOrThrowNotFoundException(?Id $orderId): void
    {
        if ($orderId) {
            $order = $this->repository->byId($orderId);
            if (!$order) {
                throw new NotFoundOrderException("Cette commande n'existe pas !");
            }
        }
    }
}