<?php

namespace Tests\Units\Order;

use App\Application\Commands\ValidateOrderCommand;
use App\Application\Entities\Order\OrderRepository;
use App\Application\Exceptions\NotFoundOrderException;
use App\Application\UseCases\ValidateOrderHandler;
use App\Application\ValueObjects\Id;
use App\Persistence\Repositories\Fruit\InMemoryFruitRepository;
use App\Persistence\Repositories\Order\InMemoryOrderRepository;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Tests\Units\Order\Builder\Director;

class ValidateOrderTest extends TestCase
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

        $handler = new ValidateOrderHandler(
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

    /**
     * @throws NotFoundOrderException
     */
    public function test_can_throw_order_not_found_exception()
    {
        $command = new ValidateOrderCommand(
            orderId: '001',
            currency: 1,
            payment: 1
        );

        $handler = new ValidateOrderHandler(
            $this->orderRepository,
            $this->fruitRepository,
        );

        $this->expectException(NotFoundOrderException::class);
        $handler->handle($command);
    }

    /**
     * @throws NotFoundOrderException
     */
    public function test_can_throw_invalid_argument_exception_with_invalid_currency()
    {
        $command = new ValidateOrderCommand(
            orderId: '001',
            currency: 5,
            payment: 1
        );

        $handler = new ValidateOrderHandler(
            $this->orderRepository,
            $this->fruitRepository,
        );

        $this->expectException(InvalidArgumentException::class);
        $handler->handle($command);
    }

    /**
     * @throws NotFoundOrderException
     */
    public function test_can_throw_invalid_argument_exception_with_invalid_payment()
    {
        $command = new ValidateOrderCommand(
            orderId: '001',
            currency: 1,
            payment: 5
        );

        $handler = new ValidateOrderHandler(
            $this->orderRepository,
            $this->fruitRepository,
        );

        $this->expectException(InvalidArgumentException::class);
        $handler->handle($command);
    }
}