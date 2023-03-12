<?php

namespace App\Application\Warehouse\Command;

class MakeItemReservationInWarehouseCommand
{
    public function __construct(
        public readonly int $warehouseId,
        public readonly int $itemId,
        public readonly int $qty,
    )
    {
    }
}