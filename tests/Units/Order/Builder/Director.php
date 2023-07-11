<?php

namespace Tests\Units\Order\Builder;

class Director
{
    private static $builder;

    public static function makeBuilder(): OrderBuilder
    {
        self::$builder = new OrderBuilderImpl();
        return self::$builder;
    }
}