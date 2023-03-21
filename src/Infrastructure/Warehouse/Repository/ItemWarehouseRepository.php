<?php

namespace App\Infrastructure\Warehouse\Repository;

use App\Domain\Warehouse\Model\ItemWarehouse;
use App\Domain\Warehouse\Repository\ItemWarehouseRepositoryInterface;
use App\Infrastructure\Warehouse\Entity\Item;
use App\Infrastructure\Warehouse\Entity\Reservation;
use App\Infrastructure\Warehouse\Entity\Warehouse;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;

class ItemWarehouseRepository extends ServiceEntityRepository implements ItemWarehouseRepositoryInterface
{
    /**
     * @inheritDoc
     */
    public function getWarehousesForItem(int $itemId, int $qty): array
    {
        $qb = $this->createQueryBuilder('item');
        $reservations = $qb;
        $reservations->select('SUM(reservation.qty) as sumOfReservations')
            ->from(Reservation::class, 'reservation')
            ->where('reservation.item.id = :itemId')
            ->setParameter('itemId', $itemId)
        ;
        $qb
            ->from(Item::class, 'item')
            ->leftJoin(Warehouse::class, 'wh')
            ->where('item.id = :itemId')
            ->andWhere($qb->expr()->gt($qb->expr()->sum('item.qty', $reservations->getDQL()), 0))
            ->setParameter('itemId', $itemId)
            ->orderBy('wh.priority', 'DESC')
            ->addOrderBy('item.qty', 'DESC')
        ;

        return $this->mapItemsToItemWarehouse($qty, $qb->getQuery()->getResult());
    }

    /**
     * @inheritDoc
     */
    public function makeReservation(int $warehouseId, int $itemId, int $qty): void
    {
        // TODO: Implement makeReservation() method.
    }

    public function removeReservation(int $warehouseId, int $itemId): void
    {
        // TODO: Implement removeReservation() method.
    }

    public function getFromInvolvedWhs(int $itemId, int $qty, array $whs): array
    {
        $qb = $this->createQueryBuilder('item');
        $reservations = $qb;
        $reservations->select('SUM(reservation.qty) as sumOfReservations')
            ->from(Reservation::class, 'reservation')
            ->where('reservation.item.id = :itemId')
            ->setParameter('itemId', $itemId)
        ;
        $qb
            ->from(Item::class, 'item')
            ->leftJoin(Warehouse::class, 'wh')
            ->where('item.id = :itemId')
            ->andWhere($qb->expr()->gt($qb->expr()->sum('item.qty', $reservations->getDQL()), 0))
            ->andWhere($qb->expr()->in('wh.id', $whs))
            ->setParameter('itemId', $itemId)
            ->orderBy('wh.priority', 'DESC')
            ->addOrderBy('item.qty', 'DESC')
        ;

        return $this->mapItemsToItemWarehouse($qty, $qb->getQuery()->getResult());
    }

    /**
     * @param Item[] $getResult
     *
     * @return ItemWarehouse[]
     */
    private function mapItemsToItemWarehouse(int $qty, array $getResult): array
    {
        return array_map(function (Item $item) use ($qty) {
            return new ItemWarehouse(
                id: $item->warehouse->id,
                itemId: $item->id,
                qty: $item->getQty() > $qty ? $qty : $item->getQty(),
            );
            }, $getResult
        );
    }
}