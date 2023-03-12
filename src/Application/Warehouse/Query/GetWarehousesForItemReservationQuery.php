<?php

namespace App\Application\Warehouse\Query;

class GetWarehousesForItemReservationQuery
{
    public function __construct(
        public readonly int $itemId,
        public readonly int $qty,
    )
    {
    }
}