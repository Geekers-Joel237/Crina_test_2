<?php

namespace Tests\Units\Basket;

use App\Application\Commands\ValidateBasketCommand;
use App\Application\Entities\Basket\Basket;
use App\Application\Entities\Fruit\FruitRepository;
use App\Application\Entities\Basket\BasketRepository;
use App\Application\Entities\Order\OrderRepository;
use App\Application\Exceptions\NotFoundBasketException;
use App\Application\UseCases\ValidateBasketHandler;
use App\Application\ValueObjects\Id;
use App\Persistence\Repositories\Fruit\InMemoryFruitRepository;
use App\Persistence\Repositories\Basket\InMemoryBasketRepository;
use App\Persistence\Repositories\Order\InMemoryOrderRepository;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

class ValidateBasketTest extends TestCase
{
    private BasketRepository $basketRepository;
    private FruitRepository $fruitRepository;
    private OrderRepository $orderRepository;

    public function setUp(): void
    {
        parent::setUp();
        $this->basketRepository = new InMemoryBasketRepository();
        $this->fruitRepository = new InMemoryFruitRepository();
        $this->orderRepository = new InMemoryOrderRepository();
    }

    /**
     * @throws NotFoundBasketException
     */
    public function test_can_validate_basket()
    {
        $basket = $this->buildBasketSUT();

        $existingAvailableFruitsBeforeOrder = $this->fruitRepository->allByReference(
            $basket->orderElements()[0]->reference()
        );

        $command = new ValidateBasketCommand(
            basketId: $basket->id()->value(),
            currency: 1,
            meanPayment: 1
        );

        $handler = new ValidateBasketHandler(
            $this->basketRepository,
            $this->fruitRepository,
            $this->orderRepository,
        );
        $response = $handler->handle($command);

        $existingBasket = $this->basketRepository->byId(new Id($command->basketId()));
        $remainingAvailableFruitsAfterOrder = $this->fruitRepository->allByReference(
            $basket->orderElements()[0]->reference()
        );

        $this->assertTrue($response->isValidated);
        $this->assertNotNull($response->orderId);
        $this->assertCount(
            count($existingAvailableFruitsBeforeOrder) -
            $existingBasket->orderElements()[0]->orderedQuantity()->value(),
            $remainingAvailableFruitsAfterOrder);
    }

    /**
     * @throws NotFoundBasketException
     */
    public function test_can_create_order()
    {
        $basket = $this->buildBasketSUT();

        $command = new ValidateBasketCommand(
            basketId: $basket->id()->value(),
            currency: 1,
            meanPayment: 1
        );

        $handler = new ValidateBasketHandler(
            $this->basketRepository,
            $this->fruitRepository,
            $this->orderRepository,
        );
        $response = $handler->handle($command);


        $this->assertTrue($response->isValidated);
        $this->assertNotNull($response->orderId);

        $order = $this->orderRepository->byId(new Id($response->orderId));
        $this->assertNotNull($order->discount());
        $this->assertNotNull($order->currency());
        $this->assertNotNull($order->meanPayment());
        $this->assertNotNull($order->paymentDate());
        $this->assertEquals($response->orderId, $order->id()->value());
        $this->assertEquals($basket->id()->value(), $order->basketId()->value());

    }
    /**
     * @throws NotFoundBasketException
     */
    public function test_can_throw_basket_not_found_exception()
    {
        $invalidBasketId = 'azerty';
        $command = new ValidateBasketCommand(
            basketId: $invalidBasketId,
            currency: 1,
            meanPayment: 1
        );

        $handler = new ValidateBasketHandler(
            $this->basketRepository,
            $this->fruitRepository,
            $this->orderRepository,
        );

        $this->expectException(NotFoundBasketException::class);
        $handler->handle($command);
    }

    /**
     * @throws NotFoundBasketException
     */
    public function test_can_throw_invalid_argument_exception_with_invalid_currency()
    {
        $invalidCurrency = 5;
        $command = new ValidateBasketCommand(
            basketId: '001',
            currency: $invalidCurrency,
            meanPayment: 1
        );

        $handler = new ValidateBasketHandler(
            $this->basketRepository,
            $this->fruitRepository,
            $this->orderRepository
        );

        $this->expectException(InvalidArgumentException::class);
        $handler->handle($command);
    }

    /**
     * @throws NotFoundBasketException
     */
    public function test_can_throw_invalid_argument_exception_with_invalid_payment()
    {
        $invalidMeanPayment = 5;
        $command = new ValidateBasketCommand(
            basketId: '001',
            currency: 1,
            meanPayment: $invalidMeanPayment
        );

        $handler = new ValidateBasketHandler(
            $this->basketRepository,
            $this->fruitRepository,
            $this->orderRepository,
        );

        $this->expectException(InvalidArgumentException::class);
        $handler->handle($command);
    }

    private function buildBasketSUT(): Basket
    {
        $basket = BasketSUT::asBuilder()->build();
        $this->basketRepository->save($basket);

        return $basket;
    }
}