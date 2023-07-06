<?php

namespace App\Application\ValueObjects;

use App\Application\Exceptions\InvalidCommandException;

readonly class FruitReference
{

    /**
     * @param string $value
     * @throws InvalidCommandException
     */
    public function __construct(private string $value)
    {
        $this->validate();
    }

    /**
     * @return void
     * @throws InvalidCommandException
     */
    private function validate(): void
    {
        if (empty($this->value)) {
            throw new InvalidCommandException("La référence est invalide !");
        }
    }
}