<?php

namespace App\Application\ValueObjects;

use App\Application\Exceptions\AmountException;

class Amount extends FloatNumberVo
{
    const TVA = 20;
    private float $value;


    /**
     * @param string $value
     */
    public function __construct(string $value)
    {
        parent::__construct($value);
    }

    /**
     * @return float
     */
    public function amountHT(): float
    {
        $amountHT = $this->value / ( 1 + (self::TVA / 100));
        return round($amountHT, 2);
    }

    /**
     * @return float
     */
    public function amountTva(): float
    {
        $amountTva = ($this->value * self::TVA) / 100;
        return round($amountTva, 2);
    }

    /**
     * @param float $value
     * @return void
     * @throws AmountException
     */
    protected function validate(float $value): void
    {
        if($value < 0) {
            throw new AmountException("Le montant ne peut pas être négatif");
        }
    }
}