<?php

namespace App\Application\Warehouse\Command;

class RemoveReservationFromWarehouseCommand
{
    public function __construct(
        public readonly int $warehouseId,
        public readonly int $itemId,
    )
    {
    }
}