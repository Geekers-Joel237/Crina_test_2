<?php

namespace App\Application\Entities\Order;

use App\Application\Entities\Basket\Basket;
use App\Application\Enums\Currency;
use App\Application\Enums\MeanPayment;
use App\Application\Exceptions\NotFoundBasketException;
use App\Application\ValueObjects\Amount;
use App\Application\ValueObjects\DateVo;
use App\Application\ValueObjects\Discount;
use App\Application\ValueObjects\Id;
use App\Application\ValueObjects\OrderElement;

class Order
{
    private ?DateVo $paymentDate;
    private ?Discount $discount;
    private ?Amount $amount;

    private function __construct(
        private readonly Id          $id,
        private readonly Id          $basketId,
        private readonly Currency    $currency,
        private readonly MeanPayment $meanPayment,
    )
    {
        $this->paymentDate = null;
        $this->discount = null;
        $this->amount = null;
    }

    public static function create(
        Basket      $basket,
        Currency    $currency,
        MeanPayment $meanPayment,
    ): self
    {
        $self = new self(new Id(time()), $basket->id(), $currency, $meanPayment);

        $self->discount = self::getDiscountFromBasket($basket->orderElements());
        $self->amount = self::getFinalAmountToBuy($self->discount, $basket->orderElements());
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

    public function paymentDate(): DateVo
    {
        return $this->paymentDate;
    }

    public function amount(): Amount
    {
        return $this->amount;
    }

    private static function getDiscountFromBasket(array $orderElements): Discount
    {
        $firstLevelToGetDiscount = 10;
        $secondLevelToGetDiscount = 20;
        $firstDiscountApply = 10;
        $secondDiscountApply = 15;

        $orderQuantity = self::getTotalOrderedQuantity($orderElements);
        return self::getDiscount($orderQuantity, $firstLevelToGetDiscount, $firstDiscountApply,
            $secondLevelToGetDiscount, $secondDiscountApply
        );
    }

    private static function getTotalOrderedQuantity(array $orderElements): int
    {
        $orderQuantity = 0;
        foreach ($orderElements as $element) {
            $orderQuantity += $element->orderedQuantity()->value();
        }
        return $orderQuantity;
    }

    private static function getDiscount(int $orderQuantity, int $firstLevelToGetDiscount, int $firstDiscountApply, int $secondLevelToGetDiscount, int $secondDiscountApply): Discount
    {
        $discount = new Discount(0);
        if ($orderQuantity > $firstLevelToGetDiscount) {
            $discount->add($firstDiscountApply);
            if ($orderQuantity > $secondLevelToGetDiscount) {
                $discount->add($secondDiscountApply);
            }
        }
        return $discount;
    }

    private static function getFinalAmountToBuy(Discount $discount, array $orderElements): Amount
    {
        $totalPrice = self::getTotalPrice($orderElements);
        return $totalPrice->sub($totalPrice->mul($discount->value())->value() / 100)->round();
    }


    /**
     * @param OrderElement[] $orderElements
     * @return Amount
     */
    private static function getTotalPrice(array $orderElements): Amount
    {
        $amount = new Amount(0.0);
        foreach ($orderElements as $element){
            $amount->add($element->reference()->unitPrice());
        }
        return $amount;
    }

}