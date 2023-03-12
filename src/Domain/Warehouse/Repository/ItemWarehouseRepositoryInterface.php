<?php

namespace App\Domain\Warehouse\Repository;

use App\Domain\Warehouse\Exception\ItemReservationFailed;
use App\Domain\Warehouse\Model\ItemWarehouse;

interface ItemWarehouseRepositoryInterface
{
    /**
     * this function probably will be 2 separate SQL's to db.
     * One to determine what qty of item we have in summary and second that will return rows with warehouses that item qty is more than requested ordered by priority.
     *
     * @return ItemWarehouse[]
     */
    public function getWarehousesForItem(int $itemId, $qty): array;

    /**
     * @throws ItemReservationFailed
     */
    public function makeReservation(int $warehouseId, int $itemId, int $qty): void;

    public function removeReservation(int $warehouseId, int $itemId): void;
}