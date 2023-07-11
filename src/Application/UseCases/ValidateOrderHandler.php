<?php

namespace App\Application\UseCases;

use App\Application\Commands\ValidateOrderCommand;
use App\Application\Entities\Fruit\FruitRepository;
use App\Application\Entities\Order\Order;
use App\Application\Entities\Order\OrderRepository;
use App\Application\Enums\Currency;
use App\Application\Enums\MeanPayment;
use App\Application\Enums\OrderStatus;
use App\Application\Exceptions\NotFoundOrderException;
use App\Application\Responses\ConfirmOrderResponse;
use App\Application\ValueObjects\Id;
use App\Application\ValueObjects\OrderElement;

readonly class ValidateOrderHandler
{



    public function __construct(
        private OrderRepository $orderRepository,
        private FruitRepository $fruitRepository,

    )
    {
    }

    /**
     * @throws NotFoundOrderException
     */
    public function handle(ValidateOrderCommand $command): ConfirmOrderResponse
    {
        $response = new ConfirmOrderResponse();
        $orderId = new Id($command->orderId());
        $currency = Currency::in($command->currency());
        $payment = MeanPayment::in($command->payment());

        $order = $this->getOrderOrThrowNotFoundException(new Id($command->orderId()));
        $discount = $this->getDiscountFromOrder($order->orderElements());
        $this->decreaseStockWithIncomingOrder($order->orderElements());
        $order->setIsValidated();

        if (OrderStatus::IS_VALIDATED->value === $order->status()->value){
            $response->isConfirmed = true;
        }
        $response->orderId = $orderId->value();
        $response->currency = $currency->humanValue();
        $response->payment = $payment->humanValue();
        $response->discount = $discount;
        return $response;
    }

    /**
     * @param OrderElement[] $orderElements
     * @return void
     */
    private function decreaseStockWithIncomingOrder(array $orderElements): void
    {
        foreach ($orderElements as $orderElement){
            $this->removeOrderElementInStock($orderElement);
        }

    }

    /**
     * @throws NotFoundOrderException
     */
    private function getOrderOrThrowNotFoundException(?Id $orderId): Order
    {
        $order = $this->orderRepository->byId($orderId);
        if (!$order) {
            throw new NotFoundOrderException("Cette commande n'existe pas !");
        }
        return $order;
    }

    /**
     * @param OrderElement $orderElement
     * @return void
     */
    private function removeOrderElementInStock(OrderElement $orderElement): void
    {
        $fruitsToRemove = array_slice(
            $this->fruitRepository->allByReference($orderElement->reference()),
            0,
            $orderElement->orderedQuantity()->value()
        );

        //TODO : marquer les fruits à retirer comme occupé
        foreach ($fruitsToRemove as $fruit){
            $fruit->setHasSold();
            $this->fruitRepository->save($fruit);
        }
    }

    private function getDiscountFromOrder(array $orderElements): string
    {
        return  '';
    }
}