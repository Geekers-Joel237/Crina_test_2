<?php

namespace App\Application\UseCases;

use App\Application\Commands\ValidateOrderCommand;
use App\Application\Entities\Fruit\FruitRepository;
use App\Application\Entities\Order\OrderRepository;
use App\Application\Enums\MeanPayment;
use App\Application\Enums\Currency;
use App\Application\Enums\OrderStatus;
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
     * @throws NotFoundFruitReferenceException
     */
    public function handle(ValidateOrderCommand $command): ValidateOrderResponse
    {
        $response = new ValidateOrderResponse();

        $orderId = new Id($command->orderId());
        $moneyType = Currency::in($command->currency());
        $meanPayment = MeanPayment::in($command->meanPayment());

        $order = $this->orderRepository->byId($orderId);
        $this->updateStockWithIncomeOrder($order->orderElements());
        $order?->setIsValidated();

        if (OrderStatus::IS_VALIDATED->value === $order->status()->value) {
            $response->isValidated = true;
        }
        return $response;
    }

    /**
     * @param OrderElement[] $orderElements
     * @return void
     * @throws NotFoundFruitReferenceException
     */
    private function updateStockWithIncomeOrder(array $orderElements): void
    {
        foreach ($orderElements as $orderElement){
            $fruitInStock = $this->getFruitByReferenceService->execute($orderElement->reference());
            $this->fruitRepository->delete($fruitInStock->id());
        }

    }

}