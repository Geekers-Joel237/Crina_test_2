<?php

namespace App\Application\UseCases\Basket;

use App\Application\Commands\ValidateBasketCommand;
use App\Application\Entities\Basket\Basket;
use App\Application\Entities\Basket\BasketRepository;
use App\Application\Entities\Fruit\Fruit;
use App\Application\Entities\Fruit\FruitRepository;
use App\Application\Entities\Order\Order;
use App\Application\Entities\Order\OrderRepository;
use App\Application\Enums\BasketStatus;
use App\Application\Enums\Currency;
use App\Application\Enums\PaymentMethod;
use App\Application\Exceptions\NotFoundBasketException;
use App\Application\Responses\ValidateBasketResponse;
use App\Application\ValueObjects\Id;
use App\Application\ValueObjects\OrderElement;

readonly class ValidateBasketHandler
{


    public function __construct(
        private BasketRepository $basketRepository,
        private FruitRepository $fruitRepository,
        private OrderRepository $orderRepository,

    )
    {
    }

    /**
     * @throws NotFoundBasketException
     */
    public function handle(ValidateBasketCommand $command): ValidateBasketResponse
    {
        $response = new ValidateBasketResponse();
        $basketId = new Id($command->basketId());
        $currency = Currency::in($command->currency());
        $paymentMethod = PaymentMethod::in($command->paymentMethod());

        $basket = $this->getBasketOrThrowNotFoundException($basketId);

        $this->decreaseStockWithIncomingBasket($basket->orderElements());
        $basket->setIsValidated();

        if (BasketStatus::IS_VALIDATED->value === $basket->status()->value) {
            $response->isValidated = true;
        }
        $order = Order::create(
            basket: $basket,
            currency: $currency,
            paymentMethod: $paymentMethod
        );
        $response->orderId = $order->id()->value();


        $this->orderRepository->save($order);
        return $response;
    }

    /**
     * @throws NotFoundBasketException
     */
    private function getBasketOrThrowNotFoundException(Id $basketId): Basket
    {
        $basket = $this->basketRepository->byId($basketId);
        if (!$basket) {
            throw new NotFoundBasketException("Cette commande n'existe pas !");
        }
        return $basket;
    }


    /**
     * @param OrderElement[] $orderElements
     * @return void
     */
    private function decreaseStockWithIncomingBasket(array $orderElements): void
    {
        foreach ($orderElements as $orderElement) {
            $this->removeOrderElementInStock($orderElement);
        }

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
        $fruitsToRemove = $this->setFruitsToRemoveHasBusy($fruitsToRemove);
        $this->setFruitsHasSold($fruitsToRemove);

    }

    /**
     * @param Fruit[] $fruitsToRemove
     * @return array
     */
    private function setFruitsToRemoveHasBusy(array $fruitsToRemove): array
    {
        foreach ($fruitsToRemove as $fruit) {
            $fruit->setHasBusy();
        }
        $this->fruitRepository->saveAll($fruitsToRemove);
        return $fruitsToRemove;
    }

    /**
     * @param array $fruitsToRemove
     * @return void
     */
    public function setFruitsHasSold(array $fruitsToRemove): void
    {
        foreach ($fruitsToRemove as $fruit) {
            $fruit->setHasSold();
        }
        $this->fruitRepository->saveAll($fruitsToRemove);
    }


}