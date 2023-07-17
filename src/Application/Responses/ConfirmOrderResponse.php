<?php

namespace App\Application\Responses;

class ConfirmOrderResponse
{

    public bool $isValidated;

    public string $orderId;
    public string $currency;
    public string $payment;
    public float $discount;
}