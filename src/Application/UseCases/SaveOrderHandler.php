<?php

namespace App\Application\UseCases;

use App\Application\Commands\SaveOrderCommand;
use App\Application\Entities\Order;
use App\Application\Entities\OrderRepository;
use App\Application\Responses\SaveOrderResponse;
use App\Application\ValueObjects\FruitReference;
use App\Application\ValueObjects\OrderedQuantity;
use App\Application\ValueObjects\OrderElement;

readonly class SaveOrderHandler
{


    public function __construct(private OrderRepository $repository)
    {
    }

    public function handle(SaveOrderCommand $command): SaveOrderResponse
    {
        $response = new SaveOrderResponse();

        $orderElement = new OrderElement(
            reference: new FruitReference($command->fruitRef),
            orderedQuantity: new OrderedQuantity($command->orderedQuantity)
        );

        $order = Order::create(
            $orderElement
        );

        $this->repository->save($order);

        $response->isSaved = true;
        $response->orderId = $order->id()->value();

        return $response;
    }
}