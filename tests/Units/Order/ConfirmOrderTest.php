<?php

namespace Tests\Units\Order;

use App\Application\Commands\ValidateOrderCommand;
use App\Application\Entities\Order\OrderRepository;
use App\Application\Exceptions\NotFoundOrderException;
use App\Application\UseCases\ConfirmOrderHandler;
use App\Application\ValueObjects\Id;
use App\Persistence\Repositories\Fruit\InMemoryFruitRepository;
use App\Persistence\Repositories\Order\InMemoryOrderRepository;
use PHPUnit\Framework\TestCase;
use Tests\Units\Order\Builder\Director;

class ConfirmOrderTest extends TestCase
{
    private OrderRepository $orderRepository;

    public function setUp(): void
    {
        parent::setUp();
        $this->orderRepository = new InMemoryOrderRepository();
        $this->fruitRepository = new InMemoryFruitRepository();
    }

    /**
     * @throws NotFoundOrderException
     */
    public function test_can_validate_order()
    {
        $order = Director::makeBuilder()->build()->order();
        $this->orderRepository->save($order);

        $existingAvailableFruitsBeforeOrder = $this->fruitRepository->allByReference($order->orderElements()[0]->reference());

        $command = new ValidateOrderCommand(
            orderId: $order->id()->value(),
            currency: 1,
            payment: 1
        );

        $handler = new ConfirmOrderHandler(
            $this->orderRepository,
            $this->fruitRepository,
        );
        $response = $handler->handle($command);

        $existingOrder = $this->orderRepository->byId(new Id($response->orderId));
        $remainingAvailableFruitsAfterOrder = $this->fruitRepository->allByReference($order->orderElements()[0]->reference());

        $this->assertTrue($response->isConfirmed);
        $this->assertNotNull($response->orderId);
        $this->assertNotNull($response->currency);
        $this->assertNotNull($response->payment);
        $this->assertNotNull($response->discount);
        $this->assertCount(count($existingAvailableFruitsBeforeOrder) - $existingOrder->orderElements()[0]->orderedQuantity()->value() ,
            $remainingAvailableFruitsAfterOrder);
    }
}