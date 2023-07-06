<?php

namespace App\Application\ValueObjects;

class OrderedQuantity
{

    public function __construct(private int $value)
    {
    }
}