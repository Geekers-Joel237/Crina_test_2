<?php

namespace App\Application\Enums;


enum OrderAction: int
{

    case ADD_TO_ORDER = 1;
    case REMOVE_FROM_ORDER = 2;

    public static function in(?int $action): self
    {
        $self = self::tryFrom($action);
        if (!$self) {
            throw new \InvalidArgumentException("Cette action n'existe pas dans le système");
        }

        return $self;
    }
}