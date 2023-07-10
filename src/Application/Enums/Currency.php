<?php

namespace App\Application\Enums;

enum Currency: int
{
    case XAF = 1;
    case EURO = 2;
    case DOLLARS = 3;

    public static function in(int $moneyType): self
    {
        $self = self::tryFrom($moneyType);
        if (!$self) {
            throw new \InvalidArgumentException("Cette monnaie n'existe pas dans le système");
        }
        return $self;
    }
}
