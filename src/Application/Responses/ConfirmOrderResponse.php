<?php

namespace App\Application\Responses;

class ConfirmOrderResponse
{

    public bool $isConfirmed;

    public string $orderId;
    public string $currency;
    public string $payment;
    public string $discount;
}