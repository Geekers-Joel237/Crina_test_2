<?php

namespace App\Application\Entities\Order;

use App\Application\Enums\Currency;
use App\Application\Enums\MeanPayment;
use App\Application\ValueObjects\DateVo;
use App\Application\ValueObjects\Discount;
use App\Application\ValueObjects\Id;

class Order
{
    private ?DateVo $paymentDate = null;


    private function __construct(
        private readonly ?Id         $id,
        private readonly Id          $basketId,
        private readonly Currency    $currency,
        private readonly MeanPayment $meanPayment,
        private readonly Discount    $discount
    )
    {
    }

    public static function create(
        Id          $basketId,
        Currency    $currency,
        MeanPayment $meanPayment,
        Discount    $discount): self
    {
        $self = new self(new Id(time()), $basketId, $currency, $meanPayment, $discount);

        $self->paymentDate = new DateVo();
        return $self;
    }

    public function id(): Id
    {
        return $this->id;
    }

    public function basketId(): Id
    {
        return $this->basketId;
    }

    public function currency(): Currency
    {
        return $this->currency;
    }

    public function meanPayment(): MeanPayment
    {
        return $this->meanPayment;
    }

    public function discount(): Discount
    {
        return $this->discount;
    }

}