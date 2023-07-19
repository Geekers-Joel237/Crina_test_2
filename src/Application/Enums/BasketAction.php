<?php

namespace App\Application\Enums;


enum BasketAction: int
{

    case ADD_TO_BASKET = 1;
    case REMOVE_FROM_BASKET = 2;
    case MODIFY_THE_QUANTITY = 3;

    public static function in(?int $action): self
    {
        $self = self::tryFrom($action);
        if (!$self) {
            throw new \InvalidArgumentException("Cette action n'existe pas dans le système");
        }

        return $self;
    }
}