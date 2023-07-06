<?php

namespace Tests\Units\Order;

use App\Application\Commands\SaveOrderCommand;
use App\Application\Entities\Order;
use App\Application\Entities\OrderRepository;
use App\Application\Exceptions\InvalidCommandException;
use App\Application\Exceptions\NotFoundOrderException;
use App\Application\UseCases\SaveOrderHandler;
use App\Application\ValueObjects\FruitReference;
use App\Application\ValueObjects\Id;
use App\Application\ValueObjects\OrderedQuantity;
use App\Application\ValueObjects\OrderElement;
use App\Persistence\Repositories\InMemoryOrderRepository;
use PHPUnit\Framework\TestCase;

class SaveOrderTest extends TestCase
{

    private OrderRepository $repository;

    public function setUp(): void
    {
        parent::setUp();
        $this->repository = new InMemoryOrderRepository();
    }

    /**
     * @throws NotFoundOrderException
     */
    public function test_can_create_an_order()
    {
        //Given
        $command = new SaveOrderCommand('ref01', 5);

        //When
        $handler = new SaveOrderHandler($this->repository);
        $response = $handler->handle($command);

        //Then
        $this->assertTrue($response->isSaved);
        $this->assertNotNull($response->orderId);
    }

    /**
     * @throws NotFoundOrderException
     */
    public function test_can_add_element_to_order()
    {
        $existingOrder = $this->buildOrderSUT();
        $command = new SaveOrderCommand(
            fruitRef: 'ref02',
            orderedQuantity: 10,
        );
        $command->orderId = $existingOrder->id()->value();

        $handler = new SaveOrderHandler($this->repository);
        $response = $handler->handle($command);

        $this->assertTrue($response->isSaved);
        $this->assertNotNull($response->orderId);
        $this->assertEquals($command->orderId, $response->orderId);
    }

    /**
     * @return void
     * @throws NotFoundOrderException
     */
    public function test_can_throw_order_not_found_exception()
    {
        $command = new SaveOrderCommand(
            fruitRef: 'ref02',
            orderedQuantity: 10,
        );
        $command->orderId = 'azeaze';

        $handler = new SaveOrderHandler($this->repository);

        $this->expectException(NotFoundOrderException::class);
        $handler->handle($command);
    }

    /**
     * @return void
     * @throws NotFoundOrderException
     */
    public function test_can_throw_invalid_command_exception()
    {
        $command = new SaveOrderCommand('', 0);

        $handler = new SaveOrderHandler($this->repository);

        $this->expectException(InvalidCommandException::class);
        $handler->handle($command);
    }

    private function buildOrderSUT(): Order
    {
        $orderElement = new OrderElement(
            reference: new FruitReference('ref001'),
            orderedQuantity: new OrderedQuantity(10)
        );
        $existingOrder = Order::create(
            orderElement: $orderElement,
            id: new Id('001')
        );

        $this->repository->save($existingOrder);

        return $existingOrder;
    }
}