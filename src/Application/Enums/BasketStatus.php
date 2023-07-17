<?php

namespace App\Application\Enums;

enum BasketStatus: int
{

    case IS_SAVED = 1;
    case IS_DESTROYED = 2;
    case IS_VALIDATED = 3;
}