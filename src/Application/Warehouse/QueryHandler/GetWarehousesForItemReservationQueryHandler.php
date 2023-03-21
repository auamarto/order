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
    ) {}

    /**
     * @return ItemWarehouse[]
     */
    public function __invoke(GetWarehousesForItemReservationQuery $query): array
    {
        $whInvolved = [];
        $itemsWh = [];
        $items = $query->items;
        usort($items, fn($a, $b) => (int) ($a->qty > $b->qty));

        foreach ($items as $item) {
            $itemsWh = array_merge($this->warehouseCalculation($item->id, $item->qty, $whInvolved), $itemsWh);
            $whInvolved = array_unique(
                array_merge(
                    array_map(function (ItemWarehouse $itemWarehouse) { return $itemWarehouse->id; }, $itemsWh),
                    $whInvolved),
                SORT_NUMERIC
            );
        }

        return $itemsWh;
    }

    /**
     * @return ItemWarehouse[]
     */
    private function warehouseCalculation(int $id, int $qty, array $whs): array
    {
        if (empty($whs)) {
            $itemsWh = $this->itemWarehouseRepository->getWarehousesForItem($id, $qty);

            return $this->trimToQty($qty, $itemsWh);
        }

        $itemsWh = $this->itemWarehouseRepository->getFromInvolvedWhs($id, $qty, $whs);
        if (!empty($itemsWh)) {
            if ($qty > $this->sumQty($itemsWh)) {
                $itemsWh = array_unique(array_merge($itemsWh, $this->itemWarehouseRepository->getWarehousesForItem($id, $qty)), SORT_REGULAR);
            }
        } else {
            $itemsWh = array_unique( array_merge($itemsWh, $this->itemWarehouseRepository->getWarehousesForItem($id, $qty)), SORT_REGULAR);
        }

        return $this->trimToQty($qty, $itemsWh);
    }

    /**
     * @param ItemWarehouse[] $itemsWh
     *
     * @return ItemWarehouse[]
     */
    private function trimToQty(int $qty, array $itemsWh): array
    {
        if ($qty > $this->sumQty($itemsWh)) {
            throw new \RuntimeException("Not enough items in warehouses");
        } elseif($qty < $this->sumQty($itemsWh)) {
            $ret = [];
            foreach ($itemsWh as $item) {
                $qty -= $item->qty;
                if($qty <= 0) {
                    $ret[] = new ItemWarehouse($item->id, $item->itemId, $item->qty - $qty);

                    break;
                } else {
                    $ret[] = new ItemWarehouse($item->id, $item->itemId, $item->qty);
                }
            }

            return $ret;
        }

        return $itemsWh;
    }

    private function sumQty(array $itemsWh): int
    {
        return array_reduce($itemsWh, function (?int $carry, ItemWarehouse $itemWarehouse) {
            $carry += $itemWarehouse->qty;

            return $carry;
        });
    }
}