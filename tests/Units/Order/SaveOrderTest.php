<?php

namespace Tests\Units\Order;

use App\Application\Commands\SaveOrderCommand;
use App\Application\UseCases\SaveOrderHandler;
use App\Persistence\Repositories\InMemoryOrderRepository;
use PHPUnit\Framework\TestCase;

class SaveOrderTest extends TestCase
{

    public function setUp(): void
    {
        parent::setUp();
    }

    public function test_can_create_an_order()
    {
        //Given
        $command = new SaveOrderCommand('ref01', 5);

        //When
        $repository = new InMemoryOrderRepository();
        $handler = new SaveOrderHandler($repository);
        $response = $handler->handle($command);

        //Then
        $this->assertTrue($response->isSaved);
        $this->assertNotNull($response->orderId);
    }
}