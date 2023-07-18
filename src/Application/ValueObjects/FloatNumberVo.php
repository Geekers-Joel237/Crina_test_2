<?php

namespace App\Application\ValueObjects;

use InvalidArgumentException;

class FloatNumberVo
{
    private float $value;

    public function __construct(?string $value)
    {
        $this->value = (float)str_replace([',', ' '], ['.', ''], $value);
        $this->validate($this->value);
    }

    /**
     * @throws InvalidArgumentException
     */
    public function add(float $value): self
    {
        $this->value += $value;
        return $this;
    }

    /**
     * @throws InvalidArgumentException
     */
    public function sub(float $value): self
    {
        $this->value -= $value;
        return $this;
    }

    public function div(float $value): self
    {
        if ($value === 0.0) {
            throw new InvalidArgumentException("Division par 0 impossible !");
        }
        $this->value /= $value;
        return $this;
    }

    public function mul(float $value): self
    {
        $this->value *= $value;
        return $this;
    }

    public function value(): float
    {
        return $this->value;
    }

    public function changeValue(float $value): self
    {
        $this->value = $value;
        return $this;
    }

    public function round(): self
    {
        $this->value = round($this->value, 2);
        return $this;
    }

    protected function validate(float $value): void
    {
        if ($this->value < 0) {
            throw new InvalidArgumentException("Valeur entrée non valide");
        }
    }
}