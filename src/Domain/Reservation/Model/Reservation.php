<?php

namespace App\Domain\Reservation\Model;

use App\Domain\Order\Model\Item;
use App\Domain\Order\Model\Order;
use App\Domain\Warehouse\Model\ItemWarehouse;

class Reservation
{
    /**
     * @param ItemWarehouse[] $warehouse
     */
    public function __construct(
        public readonly int $id,
        public readonly Order $order,
        public readonly Item $item,
        public readonly array $warehouse
    )
    {
    }
}