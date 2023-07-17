<?php

namespace App\Application\UseCases;

use App\Application\Commands\ValidateBasketCommand;
use App\Application\Entities\Fruit\Fruit;
use App\Application\Entities\Fruit\FruitRepository;
use App\Application\Entities\Basket\Basket;
use App\Application\Entities\Basket\BasketRepository;
use App\Application\Entities\Order\Order;
use App\Application\Entities\Order\OrderRepository;
use App\Application\Enums\Currency;
use App\Application\Enums\MeanPayment;
use App\Application\Enums\BasketStatus;
use App\Application\Exceptions\NotFoundBasketException;
use App\Application\Responses\ValidateBasketResponse;
use App\Application\ValueObjects\Discount;
use App\Application\ValueObjects\Id;
use App\Application\ValueObjects\OrderElement;

readonly class ValidateBasketHandler
{


    public function __construct(
        private BasketRepository $basketRepository,
        private FruitRepository  $fruitRepository,
        private OrderRepository  $orderRepository,

    )
    {
    }

    /**
     * @throws NotFoundBasketException
     */
    public
    function handle(ValidateBasketCommand $command): ValidateBasketResponse
    {
        $response = new ValidateBasketResponse();
        $basketId = new Id($command->basketId());
        $currency = Currency::in($command->currency());
        $meanPayment = MeanPayment::in($command->meanPayment());

        $basket = $this->getBasketOrThrowNotFoundException(new Id($command->basketId()));
        $this->checkAvailabilityOfOrderElementsOrThrowNotAvailableFruitReferenceException($basket->orderElements());

        $discount = $this->getDiscountFromBasket($basket->orderElements());
        $this->decreaseStockWithIncomingBasket($basket->orderElements());
        $basket->setIsValidated();

        if (BasketStatus::IS_VALIDATED->value === $basket->status()->value) {
            $response->isValidated = true;
        }
        $order = Order::create(
            basketId: $basketId,
            currency: $currency,
            meanPayment: $meanPayment,
            discount: new Discount($discount)
        );
        $response->orderId = $order->id()->value();


        $this->orderRepository->save($order);
        return $response;
    }

    /**
     * @throws NotFoundBasketException
     */
    private
    function getBasketOrThrowNotFoundException(?Id $basketId): Basket
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
    private
    function checkAvailabilityOfOrderElementsOrThrowNotAvailableFruitReferenceException(array $orderElements)
    {
    }

    /**
     * @param OrderElement[] $orderElements
     * @return float
     */
    private
    function getDiscountFromBasket(array $orderElements): float
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
    public
    function getTotalOrderedQuantity(array $orderElements): mixed
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
     * @return float
     */
    public
    function getDiscount(
        int $orderQuantity,
        int $firstLevelToGetDiscount,
        int $firstDiscountApply,
        int $secondLevelToGetDiscount,
        int $secondDiscountApply): float
    {
        $discount = 0;
        if ($orderQuantity > $firstLevelToGetDiscount) {
            $discount = $firstDiscountApply;
            $discount = $this->getSecondDiscount($orderQuantity, $secondLevelToGetDiscount, $secondDiscountApply, $discount);
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
    public
    function getSecondDiscount(
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
    private
    function decreaseStockWithIncomingBasket(array $orderElements): void
    {
        foreach ($orderElements as $orderElement) {
            $this->removeOrderElementInStock($orderElement);
        }

    }

    /**
     * @param OrderElement $orderElement
     * @return void
     */
    private
    function removeOrderElementInStock(OrderElement $orderElement): void
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
    private
    function setFruitsToRemoveHasBusy(array $fruitsToRemove): array
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
    private
    function setFruitsToRemoveHasAvailable(array $fruitsToRemove): void
    {
        foreach ($fruitsToRemove as $fruit) {
            $fruit->setHasAvailable();
        }
    }
}