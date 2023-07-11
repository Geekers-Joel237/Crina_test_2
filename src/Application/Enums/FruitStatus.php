<?php

namespace App\Application\Enums;

enum FruitStatus: int
{
    case  AVAILABLE = 1;

    case BUSY = 2;
    case SOLD = 3;

}
