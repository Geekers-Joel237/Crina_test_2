<?php

namespace App\Application\Responses;

class SaveOrderResponse
{
    public bool $isSaved = false;
    public ?string $orderId = null;
    public ?int $orderStatus = null;
}