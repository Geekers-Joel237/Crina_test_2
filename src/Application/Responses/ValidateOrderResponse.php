<?php

namespace App\Application\Responses;

class ValidateOrderResponse
{
    public ?string $orderId = null;

    public bool $isValidated = false;

}