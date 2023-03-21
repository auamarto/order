<?php

namespace App\Application\Warehouse\Query;

use App\Domain\Order\Model\Item;

class GetWarehousesForItemReservationQuery
{
    /**
     * @param Item[] $items
     */
    public function __construct(
        public readonly array $items,
    )
    {
    }
}