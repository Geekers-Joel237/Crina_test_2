<?php

namespace App\Application\Enums;

enum MeanPayment: int
{
    case MOMO = 1;
    case OM = 2;
    case CARD = 3;

    public static function in(?int $meanPayment): self
    {
        $self = self::tryFrom($meanPayment);
        if (!$self) {
            throw new \InvalidArgumentException("Ce moyen de paiement n'existe pas dans le système");
        }

        return $self;
    }
}

