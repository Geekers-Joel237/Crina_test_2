<?php

namespace App\Application\Responses;

class SaveBasketResponse
{
    public bool $isSaved = false;
    public ?string $basketId = null;
    public ?int $basketStatus = null;
}