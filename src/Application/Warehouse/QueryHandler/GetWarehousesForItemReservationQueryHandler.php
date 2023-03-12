<?php

namespace App\Application\Warehouse\QueryHandler;

use App\Application\Warehouse\Query\GetWarehousesForItemReservationQuery;
use App\Domain\Warehouse\Model\ItemWarehouse;
use App\Domain\Warehouse\Repository\ItemWarehouseRepositoryInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
class GetWarehousesForItemReservationQueryHandler
{
    public function __construct(
        public readonly ItemWarehouseRepositoryInterface $itemWarehouseRepository,
    )
    {
    }

    /**
     * @return ItemWarehouse[]
     */
    public function __invoke(GetWarehousesForItemReservationQuery $query): array
    {
        return $this->itemWarehouseRepository->getWarehousesForItem($query->itemId, $query->qty);
    }
}