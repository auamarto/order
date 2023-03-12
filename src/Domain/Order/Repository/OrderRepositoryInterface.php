<?php

namespace App\Domain\Order\Repository;

use App\Domain\Order\Model\Order;

interface OrderRepositoryInterface
{
    public function getMostProfitableOrder(): ?Order;

    public function reserve(int $orderId):void;
}