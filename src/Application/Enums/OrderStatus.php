<?php

namespace App\Application\Enums;

enum OrderStatus: int
{

    case IS_SAVED = 1;
    case IS_DESTROYED = 2;
    case IS_VALIDATED = 3;
}