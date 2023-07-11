<?php

namespace App\Application\UseCases;

use App\Application\Commands\ValidateOrderCommand;
use App\Application\Entities\Fruit\FruitRepository;
use App\Application\Entities\Order\Order;
use App\Application\Entities\Order\OrderRepository;
use App\Application\Enums\MeanPayment;
use App\Application\Enums\Currency;
use App\Application\Enums\OrderStatus;
use App\Application\Exceptions\NotAvailableFruitQuantityInStock;
use App\Application\Exceptions\NotFoundFruitReferenceException;
use App\Application\Exceptions\NotFoundOrderException;
use App\Application\Responses\ValidateOrderResponse;
use App\Application\Services\GetFruitByReferenceService;
use App\Application\ValueObjects\Id;
use App\Application\ValueObjects\OrderElement;

readonly class ValidateOrderHandle
{



    public function __construct(
        private OrderRepository $orderRepository,
        private GetFruitByReferenceService $getFruitByReferenceService,
        private FruitRepository $fruitRepository,

    )
    {
    }


    /**
     * @param ValidateOrderCommand $command
     * @return ValidateOrderResponse
     * @throws NotAvailableFruitQuantityInStock
     * @throws NotFoundOrderException
     */
    public function handle(ValidateOrderCommand $command): ValidateOrderResponse
    {
        $response = new ValidateOrderResponse();

        $orderId = new Id($command->orderId());
        $moneyType = Currency::in($command->currency());
        $meanPayment = MeanPayment::in($command->meanPayment());
        //TODO : persist moneyTYpe
        $order = $this->orderRepository->byId($orderId);
        $this->IfOrderExistValidatedItOrThrowNotFoundException($order);

        if (OrderStatus::IS_VALIDATED->value === $order->status()->value) {
            $response->isValidated = true;
        }
        return $response;
    }

    /**
     * @param OrderElement[] $orderElements
     * @return void
     * @throws NotAvailableFruitQuantityInStock
     */
    private function updateStockWithIncomeOrder(array $orderElements): void
    {
        foreach ($orderElements as $orderElement){
            $this->checkIfQuantityIsAvailableOrThrowNotAvailableFruitQuantityInStock($orderElement);
            $this->removeOtherElementRelativeToQuantityInStock($orderElement);
        }

    }

    /**
     * @param Order|null $order
     * @return void
     * @throws NotFoundOrderException|NotAvailableFruitQuantityInStock
     */
    public function IfOrderExistValidatedItOrThrowNotFoundException(?Order $order): void
    {
        if ($order) {
            $this->updateStockWithIncomeOrder($order->orderElements());
            $order->setIsValidated();
            return;
        }
        throw new NotFoundOrderException("Cette commande n'existe pas");
    }

    /**
     * @throws NotAvailableFruitQuantityInStock
     */
    private function checkIfQuantityIsAvailableOrThrowNotAvailableFruitQuantityInStock(OrderElement $orderElement): void
    {
        $fruitsInStock = $this->fruitRepository->allByReference($orderElement->reference());
        if (count($fruitsInStock) <= $orderElement->orderedQuantity()->value()){
            throw new NotAvailableFruitQuantityInStock("Nous n'avons plus cette quantite en stock pour ce fruit !");
        }
    }

    private function removeOtherElementRelativeToQuantityInStock(OrderElement $orderElement): void
    {
        $fruitsToRemove = array_slice(
            $this->fruitRepository->allByReference($orderElement->reference()),
            0,
            $orderElement->orderedQuantity()->value()
        );

        foreach ($fruitsToRemove as $fruit){
            $this->fruitRepository->delete($fruit->id());
        }
    }

}