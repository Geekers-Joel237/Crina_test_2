<?php

namespace Tests\Units\Order;

use App\Application\Commands\SaveOrderCommand;
use App\Application\Entities\Fruit\Fruit;
use App\Application\Entities\Fruit\FruitRepository;
use App\Application\Entities\Order\Order;
use App\Application\Entities\Order\OrderRepository;
use App\Application\Enums\OrderAction;
use App\Application\Enums\OrderStatus;
use App\Application\Exceptions\InvalidCommandException;
use App\Application\Exceptions\NotFoundFruitReferenceException;
use App\Application\Exceptions\NotFoundOrderElementException;
use App\Application\Exceptions\NotFoundOrderException;
use App\Application\Exceptions\FruitReferenceIsNotAvailableInStockException;
use App\Application\Services\CheckFruitReferenceAvailabilityService;
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

    private OrderRepository $orderRepository;
    private FruitRepository $fruitRepository;

    public function setUp(): void
    {
        parent::setUp();
        $this->orderRepository = new InMemoryOrderRepository();
        $this->fruitRepository = new InMemoryFruitRepository();
    }


    /**
     * @throws FruitReferenceIsNotAvailableInStockException
     * @throws NotFoundFruitReferenceException
     * @throws NotFoundOrderException
     * @throws NotFoundOrderElementException
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
        $this->assertEquals(OrderStatus::IS_SAVED->value, $response->orderStatus);
    }


    /**
     * @throws FruitReferenceIsNotAvailableInStockException
     * @throws NotFoundFruitReferenceException
     * @throws NotFoundOrderException
     * @throws NotFoundOrderElementException
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
        $savedOrder = $this->orderRepository->byId(new Id($response->orderId));
        $this->assertTrue($response->isSaved);
        $this->assertNotNull($response->orderId);
        $this->assertEquals($command->orderId, $response->orderId);
        $this->assertEquals(OrderStatus::IS_SAVED->value, $response->orderStatus);
        $this->assertNotEmpty($savedOrder->orderElements());
    }


    /**
     * @throws FruitReferenceIsNotAvailableInStockException
     * @throws NotFoundFruitReferenceException
     * @throws NotFoundOrderException
     * @throws NotFoundOrderElementException
     */
    public function test_can_update_order_when_element_is_already_present()
    {
        $existingOrder = $this->buildOrderSUT();
        $command = new SaveOrderCommand(
            $existingOrder->orderElements()[0]->reference()->value(),
            15
        );
        $command->orderId = $existingOrder->id()->value();

        $handler = $this->createSaveOrderHandler();
        $response = $handler->handle($command);

        $retrieveOrder = $this->orderRepository->byId(new Id($response->orderId));
        $this->assertTrue($response->isSaved);
        $this->assertNotNull($response->orderId);
        $this->assertEquals($command->orderId, $response->orderId);
        $this->assertEquals(OrderStatus::IS_SAVED->value, $response->orderStatus);
        $this->assertCount(count($existingOrder->orderElements()),$retrieveOrder->orderElements());
    }


    /**
     * @throws FruitReferenceIsNotAvailableInStockException
     * @throws NotFoundFruitReferenceException
     * @throws NotFoundOrderException
     * @throws NotFoundOrderElementException
     */
    public function test_can_destroy_order_while_removing_last_element_from_existing_order()
    {
        $existingOrder = $this->buildOrderSUT();
        $command = new SaveOrderCommand(
            $existingOrder->orderElements()[0]->reference()->value(),
            10
        );
        $command->orderId = $existingOrder->id()->value();
        $command->action = OrderAction::REMOVE_FROM_ORDER->value;

        $handler = $this->createSaveOrderHandler();
        $response = $handler->handle($command);

        $this->assertTrue($response->isSaved);
        $this->assertEquals(OrderStatus::IS_DESTROYED->value, $response->orderStatus);
        $this->assertNotNull($response->orderId);
        $this->assertEquals($command->orderId, $response->orderId);
    }


    /**
     * @throws NotFoundOrderElementException
     * @throws FruitReferenceIsNotAvailableInStockException
     * @throws NotFoundFruitReferenceException
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
     * @throws NotFoundOrderException
     * @throws FruitReferenceIsNotAvailableInStockException
     * @throws NotFoundFruitReferenceException
     */
    public function test_can_throw_order_element_not_found_exception()
    {
        $orderElement1 = new OrderElement(
            reference: new FruitReference('Ref01'),
            orderedQuantity: new OrderedQuantity(10)
        );
        $orderElement2 = new OrderElement(
            reference: new FruitReference('Ref02'),
            orderedQuantity: new OrderedQuantity(1)
        );
        $fruit = Fruit::create(new Id('002'), $orderElement2->reference());
        $this->fruitRepository->fruits[] = $fruit;
        $existingOrder = Order::create(
            orderElement: $orderElement1,
            id: new Id('001')
        );
        $this->orderRepository->save($existingOrder);

        $command = new SaveOrderCommand(
            $orderElement2->reference()->value(),
            1
        );
        $command->orderId = $existingOrder->id()->value();
        $command->action = OrderAction::REMOVE_FROM_ORDER->value;

        $handler = $this->createSaveOrderHandler();
        $this->expectException(NotFoundOrderElementException::class);
        $handler->handle($command);

    }

    /**
     * @throws FruitReferenceIsNotAvailableInStockException
     * @throws NotFoundFruitReferenceException
     * @throws NotFoundOrderException
     * @throws NotFoundOrderElementException
     */
    public function test_can_throw_invalid_command_exception_with_invalid_fruit_ref()
    {
        $command = new SaveOrderCommand('', 5);

        $handler = $this->createSaveOrderHandler();

        $this->expectException(InvalidCommandException::class);
        $handler->handle($command);
    }


    /**
     * @throws FruitReferenceIsNotAvailableInStockException
     * @throws NotFoundFruitReferenceException
     * @throws NotFoundOrderException
     * @throws NotFoundOrderElementException
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
     * @throws NotFoundOrderElementException
     * @throws NotFoundFruitReferenceException
     */
    public function test_can_throw_fruit_reference_is_not_available_in_stock_exception_when_ordered_not_available_in_stock_element()
    {
        $command = new SaveOrderCommand(
            'Ref03',
            5
        );

        $handler = $this->createSaveOrderHandler();
        $this->expectException(FruitReferenceIsNotAvailableInStockException::class);
        $this->expectExceptionMessage("Ce fruit n'est plus disponible en stock !");
        $handler->handle($command);
    }

    /**
     * @throws NotFoundOrderException
     * @throws NotFoundOrderElementException
     * @throws NotFoundFruitReferenceException
     */
    public function test_can_throw_fruit_reference_not_available_in_stock_exception_when_ordered_more_than_quantity_in_stock()
    {
        $existingOrder = $this->buildOrderSUT();
        $command = new SaveOrderCommand(
            $existingOrder->orderElements()[0]->reference()->value(),
            35
        );
        $fruitsByReferenceInStock = $this->fruitRepository->allByReference($existingOrder->orderElements()[0]->reference());
        $this->assertTrue($command->orderedQuantity > count($fruitsByReferenceInStock));

        $handler = $this->createSaveOrderHandler();

        $this->expectException(FruitReferenceIsNotAvailableInStockException::class);
        $this->expectExceptionMessage("La quantité demandée pour ce fruit n'est plus disponible en stock !");
        $handler->handle($command);
    }
    /**
     * @throws NotFoundOrderException|NotFoundOrderElementException|FruitReferenceIsNotAvailableInStockException
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

        $this->orderRepository->save($existingOrder);

        return $existingOrder;
    }

    /**
     * @return SaveOrderHandler
     */
    public function createSaveOrderHandler(): SaveOrderHandler
    {
        $getFruitByReferenceService = new GetFruitByReferenceService($this->fruitRepository);
        $checkFruitReferenceAvailabilityService = new CheckFruitReferenceAvailabilityService($this->fruitRepository);

        return new SaveOrderHandler(
            $this->orderRepository,
            $getFruitByReferenceService,
            $checkFruitReferenceAvailabilityService
        );
    }
}