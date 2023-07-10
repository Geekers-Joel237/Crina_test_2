<?php

namespace Tests\Units\Order;

use App\Application\Commands\ValidateOrderCommand;
use App\Application\Entities\Order\Order;
use App\Application\Entities\Order\OrderRepository;
use App\Application\Enums\OrderStatus;
use App\Application\UseCases\ValidateOrderHandle;
use App\Application\ValueObjects\FruitReference;
use App\Application\ValueObjects\Id;
use App\Application\ValueObjects\OrderedQuantity;
use App\Application\ValueObjects\OrderElement;
use App\Persistence\Repositories\Order\InMemoryOrderRepository;
use PHPUnit\Framework\TestCase;

class ValidateOrderTest extends TestCase
{
    private OrderRepository $orderRepository;

    public function setUp(): void
    {
        parent::setUp();
        $this->orderRepository = new InMemoryOrderRepository();
    }

    public function test_can_validate_Order()
    {
        $order = Order::create(
            orderElement: new OrderElement(
                new FruitReference('Ref001'),
                new OrderedQuantity(10)
            ),
            id: new Id('001'),
        );
        $this->orderRepository->save($order);
        $command = new ValidateOrderCommand($order);

        $handle = new ValidateOrderHandle();
        $response = $handle->handle($command);

        $this->assertTrue($response->isValidated);
        $this->assertNotNull($response->orderId);
        $existingOrder = $this->orderRepository->byId($order->id());
        $this->assertCount(0,$existingOrder->orderElements());
        $this->assertEquals(OrderStatus::IS_VALIDATED,$existingOrder->status());
    }
}