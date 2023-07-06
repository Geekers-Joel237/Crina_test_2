<?php

namespace App\Application\UseCases;

use App\Application\Commands\SaveOrderCommand;
use App\Application\Entities\Order;
use App\Application\Entities\OrderRepository;
use App\Application\Exceptions\NotFoundOrderException;
use App\Application\Responses\SaveOrderResponse;
use App\Application\ValueObjects\FruitReference;
use App\Application\ValueObjects\Id;
use App\Application\ValueObjects\OrderedQuantity;
use App\Application\ValueObjects\OrderElement;

readonly class SaveOrderHandler
{


    public function __construct(private OrderRepository $repository)
    {
    }

    /**
     * @throws NotFoundOrderException
     */
    public function handle(SaveOrderCommand $command): SaveOrderResponse
    {
        $response = new SaveOrderResponse();

        $orderId = $command->orderId ? new Id($command->orderId) : null;
        $orderElement = new OrderElement(
            reference: new FruitReference($command->fruitRef),
            orderedQuantity: new OrderedQuantity($command->orderedQuantity)
        );

        $this->verifyIfOrderExistsOrThrowNotFoundException($orderId);

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