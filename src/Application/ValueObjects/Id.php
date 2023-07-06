<?php

namespace App\Application\ValueObjects;

class Id
{

    public function __construct(private string $value)
    {
    }

    public function value(): string
    {
        return $this->value;
    }
}