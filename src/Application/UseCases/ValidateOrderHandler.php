<?php

namespace App\Application\UseCases;

use App\Application\Commands\ValidateOrderCommand;
use App\Application\Entities\Fruit\Fruit;
use App\Application\Entities\Fruit\FruitRepository;
use App\Application\Entities\Basket\Basket;
use App\Application\Entities\Basket\BasketRepository;
use App\Application\Enums\Currency;
use App\Application\Enums\MeanPayment;
use App\Application\Enums\BasketStatus;
use App\Application\Exceptions\NotFoundBasketException;
use App\Application\Responses\ConfirmOrderResponse;
use App\Application\ValueObjects\Id;
use App\Application\ValueObjects\OrderElement;

readonly class ValidateOrderHandler
{


    public function __construct(
        private BasketRepository $orderRepository,
        private FruitRepository  $fruitRepository,

    )
    {
    }

    /**
     * @throws NotFoundBasketException
     */
    public function handle(ValidateOrderCommand $command): ConfirmOrderResponse
    {
        $response = new ConfirmOrderResponse();
        $orderId = new Id($command->orderId());
        $currency = Currency::in($command->currency());
        $payment = MeanPayment::in($command->payment());

        $order = $this->getOrderOrThrowNotFoundException(new Id($command->orderId()));
        $this->checkAvailabilityOfOrderElementsOrThrowNotAvailableFruitReferenceException($order->orderElements());

        $discount = $this->getDiscountFromOrder($order->orderElements());
        $this->decreaseStockWithIncomingOrder($order->orderElements());
        $order->setIsValidated();

        if (BasketStatus::IS_VALIDATED->value === $order->status()->value) {
            $response->isValidated = true;
        }
        $response->orderId = $orderId->value();
        $response->currency = $currency->humanValue();
        $response->payment = $payment->humanValue();
        $response->discount = $discount;
        return $response;
    }

    /**
     * @throws NotFoundBasketException
     */
    private function getOrderOrThrowNotFoundException(?Id $orderId): Basket
    {
        $order = $this->orderRepository->byId($orderId);
        if (!$order) {
            throw new NotFoundBasketException("Cette commande n'existe pas !");
        }
        return $order;
    }

    /**
     * @param OrderElement[] $orderElements
     * @return void
     */
    private function checkAvailabilityOfOrderElementsOrThrowNotAvailableFruitReferenceException(array $orderElements)
    {
    }

    /**
     * @param OrderElement[] $orderElements
     * @return string
     */
    private function getDiscountFromOrder(array $orderElements): string
    {
        $firstLevelToGetDiscount = 10;
        $secondLevelToGetDiscount = 20;
        $firstDiscountApply = 10;
        $secondDiscountApply = 15;

        $orderQuantity = $this->getTotalOrderedQuantity($orderElements);
        return $this->getDiscount($orderQuantity, $firstLevelToGetDiscount, $firstDiscountApply,
            $secondLevelToGetDiscount, $secondDiscountApply
        );
    }

    /**
     * @param array $orderElements
     * @return int
     */
    public function getTotalOrderedQuantity(array $orderElements): mixed
    {
        $orderQuantity = 0;
        foreach ($orderElements as $element) {
            $orderQuantity += $element->orderedQuantity()->value();
        }
        return $orderQuantity;
    }

    /**
     * @param int $orderQuantity
     * @param int $firstLevelToGetDiscount
     * @param int $firstDiscountApply
     * @param int $secondLevelToGetDiscount
     * @param int $secondDiscountApply
     * @return int
     */
    public function getDiscount(
        int $orderQuantity,
        int $firstLevelToGetDiscount,
        int $firstDiscountApply,
        int $secondLevelToGetDiscount,
        int $secondDiscountApply): int
    {
        $discount = 0;
        if ($orderQuantity > $firstLevelToGetDiscount) {
            $discount = $firstDiscountApply;
            $discount = $this->checkIfApplySecondDiscountStep($orderQuantity, $secondLevelToGetDiscount, $secondDiscountApply, $discount);
        }
        return $discount;
    }

    /**
     * @param int $orderQuantity
     * @param int $secondLevelToGetDiscount
     * @param int $secondDiscountApply
     * @param int $discount
     * @return int
     */
    public function checkIfApplySecondDiscountStep(
        int $orderQuantity,
        int $secondLevelToGetDiscount,
        int $secondDiscountApply,
        int $discount): int
    {
        if ($orderQuantity > $secondLevelToGetDiscount) {
            $discount += $secondDiscountApply;
        }
        return $discount;
    }

    /**
     * @param OrderElement[] $orderElements
     * @return void
     */
    private function decreaseStockWithIncomingOrder(array $orderElements): void
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
        foreach ($fruitsToRemove as $fruit) {
            $fruit->setHasSold();
        }
        $this->fruitRepository->saveAll($fruitsToRemove);

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
     * @param Fruit[] $fruitsToRemove
     * @return void
     */
    private function setFruitsToRemoveHasAvailable(array $fruitsToRemove): void
    {
        foreach ($fruitsToRemove as $fruit) {
            $fruit->setHasAvailable();
        }
    }
}