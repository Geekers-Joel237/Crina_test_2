<?php

namespace App\Application\Enums;

enum PaymentMethod: int
{
    case MTN_MONEY = 1;
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

    public function humanValue(): string
    {
        return match ($this->value){
            1 => 'Mobile Money',
            2 => 'Orange Money',
            3 => 'Master Card'
        };
    }
}

