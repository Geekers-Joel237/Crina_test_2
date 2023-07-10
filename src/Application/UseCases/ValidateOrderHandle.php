<?php

namespace App\Application\UseCases;

use App\Application\Commands\ValidateOrderCommand;
use App\Application\Enums\OrderStatus;
use App\Application\Responses\ValidateOrderResponse;

class ValidateOrderHandle
{
    public function __construct()
    {
    }

    public function handle(ValidateOrderCommand $command): ValidateOrderResponse
    {
        $response = new ValidateOrderResponse();
        $order = $command->order();
        $response->orderId = $order->id()->value();
        $order->setIsValidated();
        if ($order->status() === OrderStatus::IS_VALIDATED){
            $response->isValidated = true;
        }
        return $response;
    }
}