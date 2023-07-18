<?php

namespace Tests\Units\Basket;

use App\Application\Commands\SaveBasketCommand;
use App\Application\Entities\Fruit\FruitRepository;
use App\Application\Entities\Basket\Basket;
use App\Application\Entities\Basket\BasketRepository;
use App\Application\Enums\BasketAction;
use App\Application\Enums\BasketStatus;
use App\Application\Exceptions\InvalidCommandException;
use App\Application\Exceptions\NotFoundFruitReferenceException;
use App\Application\Exceptions\NotFoundOrderElementException;
use App\Application\Exceptions\NotFoundBasketException;
use App\Application\Exceptions\NotAvailableInStockFruitReferenceException;
use App\Application\Services\CheckFruitReferenceAvailabilityService;
use App\Application\Services\GetFruitByReferenceService;
use App\Application\UseCases\SaveBasketHandler;
use App\Application\ValueObjects\FruitReference;
use App\Application\ValueObjects\Id;
use App\Application\ValueObjects\OrderedQuantity;
use App\Application\ValueObjects\OrderElement;
use App\Persistence\Repositories\Fruit\InMemoryFruitRepository;
use App\Persistence\Repositories\Basket\InMemoryBasketRepository;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

class SaveBasketTest extends TestCase
{

    private BasketRepository $basketRepository;
    private FruitRepository $fruitRepository;

    public function setUp(): void
    {
        parent::setUp();
        $this->basketRepository = new InMemoryBasketRepository();
        $this->fruitRepository = new InMemoryFruitRepository();
    }


    /**
     * @throws NotAvailableInStockFruitReferenceException
     * @throws NotFoundFruitReferenceException
     * @throws NotFoundBasketException
     * @throws NotFoundOrderElementException
     */
    public function test_can_create_a_basket()
    {
        //Given
        $existingBasket = $this->buildBasketSUT();
        $command = new SaveBasketCommand(
            $existingBasket->orderElements()[0]->reference()->value(),
            5
        );

        //When
        $handler = $this->createSaveBasketHandler();
        $response = $handler->handle($command);

        //Then
        $this->assertTrue($response->isSaved);
        $this->assertNotNull($response->basketId);
        $this->assertEquals(BasketStatus::IS_SAVED->value, $response->basketStatus);
    }

    private function buildBasketSUT(): Basket
    {
        $orderElement = new OrderElement(
            reference: new FruitReference('Ref01'),
            orderedQuantity: new OrderedQuantity(10)
        );
        $existingBasket = Basket::create(
            orderElement: $orderElement,
            id: new Id('001')
        );

        $this->basketRepository->save($existingBasket);

        return $existingBasket;
    }

    /**
     * @return SaveBasketHandler
     */
    private function createSaveBasketHandler(): SaveBasketHandler
    {
        $getFruitByReferenceService = new GetFruitByReferenceService($this->fruitRepository);
        $checkFruitReferenceAvailabilityService = new CheckFruitReferenceAvailabilityService($this->fruitRepository);

        return new SaveBasketHandler(
            $this->basketRepository,
            $getFruitByReferenceService,
            $checkFruitReferenceAvailabilityService
        );
    }

    /**
     * @throws NotAvailableInStockFruitReferenceException
     * @throws NotFoundFruitReferenceException
     * @throws NotFoundBasketException
     * @throws NotFoundOrderElementException
     */
    public function test_can_add_element_to_basket()
    {
        $existingBasket = $this->buildBasketSUT();
        $command = new SaveBasketCommand(
            $existingBasket->orderElements()[0]->reference()->value(),
            10
        );
        $command->basketId = $existingBasket->id()->value();

        $handler = $this->createSaveBasketHandler();
        $response = $handler->handle($command);
        $this->assertTrue($response->isSaved);
        $this->assertNotNull($response->basketId);
        $this->assertEquals($command->basketId, $response->basketId);
        $this->assertEquals(BasketStatus::IS_SAVED->value, $response->basketStatus);
    }

    /**
     * @throws NotAvailableInStockFruitReferenceException
     * @throws NotFoundFruitReferenceException
     * @throws NotFoundBasketException
     * @throws NotFoundOrderElementException
     */
    public function test_can_update_basket_where_add_already_present_element()
    {
        $existingBasket = $this->buildBasketSUT();
        $command = new SaveBasketCommand(
            $existingBasket->orderElements()[0]->reference()->value(),
            15
        );
        $command->basketId = $existingBasket->id()->value();
        $beforeOrderQuantity = $existingBasket->orderElements()[0]->orderedQuantity()->value();
        $handler = $this->createSaveBasketHandler();
        $response = $handler->handle($command);

        $retrieveBasket = $this->basketRepository->byId(new Id($response->basketId));
        $this->assertTrue($response->isSaved);
        $this->assertNotNull($response->basketId);
        $this->assertEquals($command->basketId, $response->basketId);
        $this->assertEquals(BasketStatus::IS_SAVED->value, $response->basketStatus);
        $this->assertCount(count($existingBasket->orderElements()), $retrieveBasket->orderElements());
        $this->assertEquals(
            $retrieveBasket->orderElements()[0]->orderedQuantity()->value(),
            $command->orderedQuantity + $beforeOrderQuantity);
    }

    /**
     * @throws NotAvailableInStockFruitReferenceException
     * @throws NotFoundFruitReferenceException
     * @throws NotFoundBasketException
     * @throws NotFoundOrderElementException
     */
    public function test_can_destroy_basket_while_removing_last_element_from_existing_basket()
    {
        $existingBasket = $this->buildBasketSUT();
        $command = new SaveBasketCommand(
            $existingBasket->orderElements()[0]->reference()->value()
        );
        $command->basketId = $existingBasket->id()->value();
        $command->action = BasketAction::REMOVE_FROM_BASKET->value;

        $handler = $this->createSaveBasketHandler();
        $response = $handler->handle($command);

        $this->assertTrue($response->isSaved);
        $this->assertEquals(BasketStatus::IS_DESTROYED->value, $response->basketStatus);
        $this->assertNotNull($response->basketId);
        $this->assertEquals($command->basketId, $response->basketId);
    }

    /**
     * @throws NotAvailableInStockFruitReferenceException
     * @throws NotFoundFruitReferenceException
     * @throws NotFoundBasketException
     * @throws NotFoundOrderElementException
     */
    public function test_can_remove_order_element_from_existing_basket()
    {
        $basketWithManyOrderElements = $this->buildBasketWithManyOrderElements();
        $command = new SaveBasketCommand(
            fruitRef: $basketWithManyOrderElements->orderElements()[0]->reference()->value()
        );

        $command->basketId = $basketWithManyOrderElements->id()->value();
        $command->action = BasketAction::REMOVE_FROM_BASKET->value;
        $orderElementsBeforeOrder = count($basketWithManyOrderElements->orderElements());
        $handler = $this->createSaveBasketHandler();
        $response = $handler->handle($command);

        $retrieveOrder = $this->basketRepository->byId(new Id($command->basketId));
        $this->assertTrue($response->isSaved);
        $this->assertEquals(BasketStatus::IS_SAVED->value, $response->basketStatus);
        $this->assertNotNull($response->basketId);
        $this->assertEquals($command->basketId, $response->basketId);
        $this->assertTrue(count($retrieveOrder->orderElements()) === $orderElementsBeforeOrder - 1);
    }

    /**
     * @throws NotFoundOrderElementException
     */
    private function buildBasketWithManyOrderElements(): Basket
    {

        $orderElement1 = new OrderElement(
            reference: new FruitReference('Ref02'),
            orderedQuantity: new OrderedQuantity(2)
        );
        $orderElement2 = new OrderElement(
            reference: new FruitReference('Ref03'),
            orderedQuantity: new OrderedQuantity(1)
        );
        $orderWithManyOrderElements = BasketSUT::asBuilder()
            ->withOtherElement($orderElement1)
            ->withOtherElement($orderElement2)
            ->build();
        $this->basketRepository->save($orderWithManyOrderElements);

        return $orderWithManyOrderElements;
    }

    /**
     * @throws NotFoundOrderElementException
     * @throws NotAvailableInStockFruitReferenceException
     * @throws NotFoundFruitReferenceException
     */
    public function test_can_throw_basket_not_found_exception()
    {
        $existingBasket = $this->buildBasketSUT();
        $command = new SaveBasketCommand(
            $existingBasket->orderElements()[0]->reference()->value(),
            10
        );
        $command->basketId = 'amaze';

        $handler = $this->createSaveBasketHandler();

        $this->expectException(NotFoundBasketException::class);
        $handler->handle($command);
    }

    /**
     * @throws NotFoundBasketException
     * @throws NotAvailableInStockFruitReferenceException
     * @throws NotFoundFruitReferenceException
     */
    public function test_can_throw_order_element_not_found_in_basket_exception()
    {
        $orderElement = new OrderElement(
            reference: new FruitReference('Ref02'),
            orderedQuantity: new OrderedQuantity(1)
        );

        $existingBasket = $this->buildBasketSUT();

        $command = new SaveBasketCommand(
            $orderElement->reference()->value(),
            1
        );
        $command->basketId = $existingBasket->id()->value();
        $command->action = BasketAction::REMOVE_FROM_BASKET->value;

        $handler = $this->createSaveBasketHandler();
        $this->expectException(NotFoundOrderElementException::class);
        $handler->handle($command);

    }

    /**
     * @throws NotAvailableInStockFruitReferenceException
     * @throws NotFoundFruitReferenceException
     * @throws NotFoundBasketException
     * @throws NotFoundOrderElementException
     */
    public function test_can_throw_invalid_command_exception_with_invalid_fruit_ref()
    {
        $command = new SaveBasketCommand('', 5);

        $handler = $this->createSaveBasketHandler();

        $this->expectException(InvalidCommandException::class);
        $handler->handle($command);
    }

    /**
     * @throws NotAvailableInStockFruitReferenceException
     * @throws NotFoundFruitReferenceException
     * @throws NotFoundBasketException
     * @throws NotFoundOrderElementException
     */
    public function test_can_throw_invalid_command_exception_with_invalid_ordered_quantity()
    {
        $existingBasket = $this->buildBasketSUT();
        $command = new SaveBasketCommand(
            $existingBasket->orderElements()[0]->reference()->value(),
            -5
        );

        $handler = $this->createSaveBasketHandler();

        $this->expectException(InvalidCommandException::class);
        $handler->handle($command);
    }

    /**
     * @throws NotFoundBasketException
     * @throws NotFoundOrderElementException
     * @throws NotFoundFruitReferenceException
     */
    public function test_can_throw_fruit_reference_not_available_in_stock_exception_when_ordered_not_available_in_stock_element()
    {
        $notAvailableFruitReference = 'Ref03';
        $command = new SaveBasketCommand(
            $notAvailableFruitReference,
            5
        );

        $handler = $this->createSaveBasketHandler();
        $this->expectException(NotAvailableInStockFruitReferenceException::class);
        $this->expectExceptionMessage("Ce fruit n'est plus disponible en stock !");
        $handler->handle($command);
    }

    /**
     * @throws NotFoundBasketException
     * @throws NotFoundOrderElementException
     * @throws NotFoundFruitReferenceException
     */
    public function test_can_throw_fruit_reference_not_available_in_stock_exception_when_ordered_more_than_quantity_in_stock()
    {
        $quantityGreaterThanQuantityInStock = 35;
        $existingBasket = $this->buildBasketSUT();
        $command = new SaveBasketCommand(
            $existingBasket->orderElements()[0]->reference()->value(),
            $quantityGreaterThanQuantityInStock
        );
        $fruitsByReferenceInStock = $this->fruitRepository->allByReference($existingBasket->orderElements()[0]->reference());
        $this->assertTrue($command->orderedQuantity > count($fruitsByReferenceInStock));

        $handler = $this->createSaveBasketHandler();

        $this->expectException(NotAvailableInStockFruitReferenceException::class);
        $this->expectExceptionMessage("La quantité demandée pour ce fruit n'est plus disponible en stock !");
        $handler->handle($command);
    }

    /**
     * @throws NotFoundBasketException|NotFoundOrderElementException|NotAvailableInStockFruitReferenceException
     */
    public function test_can_throw_fruit_reference_not_found_exception()
    {
        $notFoundFruitReference = 'Ref10';
        $command = new SaveBasketCommand(
            fruitRef: $notFoundFruitReference,
            orderedQuantity: 10,
        );

        $handler = $this->createSaveBasketHandler();

        $this->expectException(NotFoundFruitReferenceException::class);
        $handler->handle($command);
    }

    /**
     * @throws NotFoundBasketException
     * @throws NotFoundFruitReferenceException
     * @throws NotFoundOrderElementException
     * @throws NotAvailableInStockFruitReferenceException
     */
    public function test_can_throw_invalid_argument_exception_when_not_give_the_quantity_in_case_different_from_the_remove()
    {
        $existingBasket = $this->buildBasketSUT();
        $command = new SaveBasketCommand($existingBasket->orderElements()[0]->reference()->value());

        $handler = $this->createSaveBasketHandler();
        $this->expectException(InvalidArgumentException::class);
        $handler->handle($command);
    }

}