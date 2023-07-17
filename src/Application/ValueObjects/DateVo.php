<?php

namespace App\Application\ValueObjects;

use App\Application\Exceptions\InvalidDateException;
use Exception;
use InvalidArgumentException;

class DateVo
{
    private string $value;
    private ?string $format;

    /**
     * @throws InvalidArgumentException
     */
    public function __construct(?string $value = null, ?string $format = null)
    {
        if ($value) {
            $this->value = $value;
        } else {
            $this->value = date('Y-m-d H:i:s');
        }
        $this->format = $format ?? 'Y-m-d H:i:s';
        $this->validate();
    }

    public function addDays(int $day): static
    {
        $this->value = date($this->format, strtotime($this->value . ' +' . $day . 'day'));
        return $this;
    }

    /**
     * @throws Exception
     */
    public function intervalDaysBetween(string $date): int
    {
        $interval = (new \DateTime($this->value))->diff(new \DateTime($date));
        return $interval->days;
    }

    /**
     * @throws Exception
     */
    public function numberOfDaysFrom(string $date): int
    {
        return $this->intervalDaysBetween($date) + 2;
    }

    /**
     * @throws Exception
     */
    public function numberDaysOfYear(): int
    {
        $firstDayDate = new \DateTime($this->formatY() . "-01-01");
        $lastDayDate = new \DateTime($this->formatY() . "-12-31");
        $intervalDays = $lastDayDate->diff($firstDayDate);
        return $intervalDays->days + 1;
    }

    public function createFromFormatDMY(): \DateTime
    {
        return \DateTime::createFromFormat('d/m/Y', $this->value);
    }

    /**
     * @throws Exception
     */
    public function formatYMDHIS(): string
    {
        if (!$this->value) {
            throw new \Exception('la date n\'est pas valide');
        }
        return (new \DateTime($this->value))->format('Y-m-d h:i:s');
    }

    /**
     * @throws Exception
     */
    public function formatDMYHIS(): string
    {
        return (new \DateTime($this->value))->format('d/m/Y h:i:s');
    }

    /**
     * @throws Exception
     */
    public function formatYMD(): string
    {
        if (!$this->value) {
            throw new \Exception('la valeur de la date doit être renseignée');
        }
        return (new \DateTime($this->value))->format('Y-m-d');
    }

    /**
     * @throws InvalidDateException
     * @throws Exception
     */
    public function formatY(): string
    {
        if (!$this->value) {
            throw new InvalidDateException('la valeur de la date doit être renseignée');
        }
        return (new \DateTime($this->value))->format('Y');
    }

    /**
     * @throws Exception
     */
    public function formatDMY(): string
    {
        if (!$this->value) {
            throw new \Exception('la valeur de la date doit être renseignée');
        }
        return (new \DateTime($this->value))->format('d/m/Y');
    }

    /**
     * @param string $format
     * @return bool
     */
    public function isValid(string $format = 'Y-m-d H:i:s'): bool
    {
        $d = \DateTime::createFromFormat($format, $this->value);
        return $d && $d->format($format) == $this->value;
    }

    /**
     * @return void
     * @throws InvalidArgumentException
     */
    public function validate(): void
    {
        $d = \DateTime::createFromFormat($this->format, $this->value);
        if (!$d || $d->format($this->format) != $this->value) {
            throw new \InvalidArgumentException("La date entrée n'est pas valide");
        }
    }

}