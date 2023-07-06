<?php

namespace App\Application\Entities;

interface OrderRepository
{
    public function save(Order $order): void;
}