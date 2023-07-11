<?php

namespace Tests\Units\Order;

use App\Application\Commands\ValidateOrderCommand;
use App\Application\Entities\Fruit\FruitRepository;
use App\Application\Entities\Order\Order;
use App\Application\Entities\Order\OrderRepository;
use App\Application\Enums\OrderStatus;
use App\Application\Exceptions\NotAvailableFruitQuantityInStock;
use App\Application\Exceptions\NotFoundFruitReferenceException;
use App\Application\Exceptions\NotFoundOrderException;
use App\Application\Services\GetFruitByReferenceService;
use App\Application\UseCases\ValidateOrderHandle;
use App\Application\ValueObjects\FruitReference;
use App\Application\ValueObjects\Id;
use App\Application\ValueObjects\OrderedQuantity;
use App\Application\ValueObjects\OrderElement;
use App\Persistence\Repositories\Fruit\InMemoryFruitRepository;
use App\Persistence\Repositories\Order\InMemoryOrderRepository;
use PHPUnit\Framework\TestCase;

class ValidateOrderTest extends TestCase
{
    private OrderRepository $orderRepository;
    private FruitRepository $fruitRepository;
    private GetFruitByReferenceService $getFruitByReferenceService;

    public function setUp(): void
    {
        parent::setUp();
        $this->orderRepository = new InMemoryOrderRepository();
        $this->fruitRepository = new InMemoryFruitRepository();
        $this->getFruitByReferenceService = new GetFruitByReferenceService($this->fruitRepository);
    }


    /**
     * @throws NotFoundOrderException
     * @throws NotAvailableFruitQuantityInStock
     */
    public function test_can_validate_Order()
    {

        $order = Order::create(
            orderElement: new OrderElement(
                new FruitReference('Ref01'),
                new OrderedQuantity(2)
            ),
            id: new Id('001'),
        );

        $existingFruitsBeforeOrder = $this->fruitRepository->allByReference($order->orderElements()[0]->reference());

        $this->orderRepository->save($order);
        $command = new ValidateOrderCommand(
            orderId: $order->id()->value(),
            currency: 1,
            meanPayment: 1
        );

        $handle = new ValidateOrderHandle(
            $this->orderRepository,
            $this->getFruitByReferenceService,
            $this->fruitRepository
        );
        $response = $handle->handle($command);

        $existingOrder = $this->orderRepository->byId(new Id($command->orderId()));
        $remainingFruitsAfterOrder = $this->fruitRepository->allByReference($order->orderElements()[0]->reference());


        $this->assertTrue($response->isValidated);
        $this->assertEquals(OrderStatus::IS_VALIDATED->value, $existingOrder->status()->value);
        $this->assertCount(count($existingFruitsBeforeOrder) - $existingOrder->orderElements()[0]->orderedQuantity()->value() ,
            $remainingFruitsAfterOrder);
    }

    /**
     * @throws NotAvailableFruitQuantityInStock
     */
    public function test_can_throw_not_found_order_exception()
    {
        $command = new ValidateOrderCommand(
            orderId: '002',
            currency: 1,
            meanPayment: 1
        );

        $handle = new ValidateOrderHandle(
            $this->orderRepository,
            $this->getFruitByReferenceService,
            $this->fruitRepository
        );

        $this->expectException(NotFoundOrderException::class);
        $handle->handle($command);

    }

    /**
     * @throws NotFoundOrderException
     */
    public function test_can_throw_exception_when_ordered_quantity_is_greater_than_in_stock()
    {
        $order = Order::create(
            orderElement: new OrderElement(
                new FruitReference('Ref01'),
                new OrderedQuantity(30)
            ),
            id: new Id('001'),
        );

        $existingFruitsBeforeOrder = $this->fruitRepository->allByReference($order->orderElements()[0]->reference());

        $this->orderRepository->save($order);
        $command = new ValidateOrderCommand(
            orderId: $order->id()->value(),
            currency: 1,
            meanPayment: 1
        );

        $existingOrder = $this->orderRepository->byId(new Id($command->orderId()));
        $this->assertTrue($existingOrder->orderElements()[0]->orderedQuantity()->value() > count($existingFruitsBeforeOrder));
        $handle = new ValidateOrderHandle(
            $this->orderRepository,
            $this->getFruitByReferenceService,
            $this->fruitRepository
        );
        $this->expectException(NotAvailableFruitQuantityInStock::class);
        $handle->handle($command);

    }
}