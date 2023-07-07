<?php

namespace Tests\Units\Order;

use App\Application\Commands\SaveOrderCommand;
use App\Application\Entities\Fruit\FruitRepository;
use App\Application\Entities\Order\Order;
use App\Application\Entities\Order\OrderRepository;
use App\Application\Exceptions\InvalidCommandException;
use App\Application\Exceptions\NotFoundFruitReferenceException;
use App\Application\Exceptions\NotFoundOrderException;
use App\Application\Services\GetFruitByReferenceService;
use App\Application\UseCases\SaveOrderHandler;
use App\Application\ValueObjects\FruitReference;
use App\Application\ValueObjects\Id;
use App\Application\ValueObjects\OrderedQuantity;
use App\Application\ValueObjects\OrderElement;
use App\Persistence\Repositories\Fruit\InMemoryFruitRepository;
use App\Persistence\Repositories\Order\InMemoryOrderRepository;
use PHPUnit\Framework\TestCase;

class SaveOrderTest extends TestCase
{

    private OrderRepository $repository;
    private FruitRepository $fruitRepository;

    public function setUp(): void
    {
        parent::setUp();
        $this->repository = new InMemoryOrderRepository();
        $this->fruitRepository = new InMemoryFruitRepository();
    }

    /**
     * @throws NotFoundFruitReferenceException
     * @throws NotFoundOrderException
     */
    public function test_can_create_an_order()
    {
        //Given
        $existingOrder = $this->buildOrderSUT();
        $command = new SaveOrderCommand(
            $existingOrder->orderElements()[0]->reference()->value(),
            5
        );

        //When
        $handler = $this->createSaveOrderHandler();
        $response = $handler->handle($command);

        //Then
        $this->assertTrue($response->isSaved);
        $this->assertNotNull($response->orderId);
    }

    /**
     * @throws NotFoundFruitReferenceException
     * @throws NotFoundOrderException
     */
    public function test_can_add_element_to_order()
    {
        $existingOrder = $this->buildOrderSUT();
        $command = new SaveOrderCommand(
            $existingOrder->orderElements()[0]->reference()->value(),
            10
        );
        $command->orderId = $existingOrder->id()->value();

        $handler = $this->createSaveOrderHandler();
        $response = $handler->handle($command);

        $this->assertTrue($response->isSaved);
        $this->assertNotNull($response->orderId);
        $this->assertEquals($command->orderId, $response->orderId);
    }

    /**
     * @throws NotFoundOrderException
     * @throws NotFoundFruitReferenceException
     */
    public function test_can_remove_element_from_existing_order()
    {
        $existingOrder = $this->buildOrderSUT();
        $command = new SaveOrderCommand(
            $existingOrder->orderElements()[0]->reference()->value(),
            10
        );
        $command->orderId = $existingOrder->id()->value();

        $handler = $this->createSaveOrderHandler();
        $response = $handler->handle($command);

        $this->assertTrue($response->isSaved);
        $this->assertNotNull($response->orderId);
        $this->assertEquals($command->orderId, $response->orderId);
    }

    /**
     * @return void
     * @throws NotFoundFruitReferenceException
     * @throws NotFoundOrderException
     */
    public function test_can_throw_order_not_found_exception()
    {
        $existingOrder = $this->buildOrderSUT();
        $command = new SaveOrderCommand(
            $existingOrder->orderElements()[0]->reference()->value(),
            10
        );
        $command->orderId = 'azeaze';

        $handler = $this->createSaveOrderHandler();

        $this->expectException(NotFoundOrderException::class);
        $handler->handle($command);
    }

    /**
     * @return void
     * @throws NotFoundFruitReferenceException
     * @throws NotFoundOrderException
     */
    public function test_can_throw_invalid_command_exception_with_invalid_fruit_ref()
    {
        $command = new SaveOrderCommand('', 5);

        $handler = $this->createSaveOrderHandler();

        $this->expectException(InvalidCommandException::class);
        $handler->handle($command);
    }

    /**
     * @throws NotFoundOrderException
     * @throws NotFoundFruitReferenceException
     */
    public function test_can_throw_invalid_command_exception_with_invalid_ordered_quantity()
    {
        $existingOrder = $this->buildOrderSUT();
        $command = new SaveOrderCommand(
            $existingOrder->orderElements()[0]->reference()->value(),
            -5
        );

        $handler = $this->createSaveOrderHandler();

        $this->expectException(InvalidCommandException::class);
        $handler->handle($command);
    }

    /**
     * @throws NotFoundOrderException
     */
    public function test_can_throw_fruit_reference_not_found_exception()
    {
        $command = new SaveOrderCommand(
            fruitRef: 'Ref10',
            orderedQuantity: 10,
        );

        $handler = $this->createSaveOrderHandler();

        $this->expectException(NotFoundFruitReferenceException::class);
        $handler->handle($command);
    }

    private function buildOrderSUT(): Order
    {
        $orderElement = new OrderElement(
            reference: new FruitReference('Ref01'),
            orderedQuantity: new OrderedQuantity(10)
        );
        $existingOrder = Order::create(
            orderElement: $orderElement,
            id: new Id('001')
        );

        $this->repository->save($existingOrder);

        return $existingOrder;
    }

    /**
     * @return SaveOrderHandler
     */
    public function createSaveOrderHandler(): SaveOrderHandler
    {
        $getFruitByReferenceService = new GetFruitByReferenceService($this->fruitRepository);

        return new SaveOrderHandler(
            $this->repository,
            $getFruitByReferenceService
        );
    }
}