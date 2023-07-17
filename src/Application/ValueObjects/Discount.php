<?php

namespace App\Application\ValueObjects;

use InvalidArgumentException;

class Discount
{

    private float $value;

    public function __construct(float $value)
    {
        $this->value = (float)str_replace([',', ' '], ['.', ''], $value);
        $this->validate();
    }

    public function value(): float
    {
        return $this->value;
    }

    public function add(float $value): self
    {
        $this->value += $value;
        return $this;
    }
    public function sub(float $value): self
    {
        $this->value -= $value;
        return $this;
    }
    public function round(): self
    {
        $this->value = round($this->value, 2);
        return $this;
    }
    private function validate(): void
    {
        if ($this->value < 0) {
            throw new InvalidArgumentException("Valeur entrée non valide");
        }
    }
}