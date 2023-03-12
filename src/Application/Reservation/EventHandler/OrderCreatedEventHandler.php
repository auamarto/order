<?php

namespace App\Application\Reservation\EventHandler;

use App\Application\Order\Event\OrderCreatedEvent;
use App\Application\Order\Query\GetMostProfitableOrderQuery;
use App\Application\Reservation\Event\ReservationHasBeenMadeEvent;
use App\Application\Reservation\Exception\RequiredQtyHasNotBeenReservedException;
use App\Application\Warehouse\Command\MakeItemReservationInWarehouseCommand;
use App\Application\Warehouse\Command\RemoveReservationFromWarehouseCommand;
use App\Application\Warehouse\Query\GetWarehousesForItemReservationQuery;
use App\Domain\Order\Model\Order;
use App\Domain\Reservation\Model\Reservation;
use App\Domain\Reservation\Repository\ReservationRepositoryInterface;
use App\Domain\Warehouse\Model\ItemWarehouse;
use Psr\Log\LoggerInterface;
use Symfony\Component\Lock\LockFactory;
use Symfony\Component\Lock\LockInterface;
use Symfony\Component\Lock\SharedLockStoreInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Messenger\Exception\LogicException;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Stamp\HandledStamp;

#[AsMessageHandler]
/**
 * This is more or less representation of bounded context for whole process and Reservation Domain is just part of it that holds reservations.
 */
class OrderCreatedEventHandler
{
    private LockFactory $warehouseItemLockingService;

    public function __construct(
        private readonly MessageBusInterface $queryBus,
        private readonly MessageBusInterface $commandBus,
        private readonly MessageBusInterface $eventBus,
        readonly SharedLockStoreInterface $store,
        private readonly ReservationRepositoryInterface $reservationRepository,
        private readonly LoggerInterface $logger,
    )
    {
        $this->warehouseItemLockingService = new LockFactory($store);
    }

    public function __invoke(OrderCreatedEvent $orderCreated)
    {
        $order = $this->getMostProfitableOrderToProcess();
        if (null === $order) {
            $this->logger->warning("There is no available orders to process");

            return;
        }
        $this->logger->info("Order reservation process started", ['order' => $order->id]);

        foreach ($order->getItems() as $item)
        {
            $chosenWarehouses = $this->getWarehousesForItemReservation($item->id, $item->qty);
            $itemLocks = $this->createLocksForItem($chosenWarehouses);
            $reservedQty = 0;
            foreach ($chosenWarehouses as $itemWarehouse) {
                try {
                    $this->commandBus->dispatch(new MakeItemReservationInWarehouseCommand($itemWarehouse->id,$itemWarehouse->itemId, $itemWarehouse->qty));
                    $reservedQty += $itemWarehouse->qty;
                } catch (\RuntimeException $exception)
                {
                   $this->revertTransation($order, $exception, $chosenWarehouses, $itemLocks);
                }
            }
            if ($item->qty > $reservedQty) {
                $exception = new RequiredQtyHasNotBeenReservedException('Not enough qty.');
                $this->revertTransation($order, $exception, $chosenWarehouses, $itemLocks);

                throw $exception;
            }
            $reservation = new Reservation(time(), $order, $item, $chosenWarehouses);
            $this->reservationRepository->save($reservation);
            $this->releaseLocks($itemLocks);
        }

        $this->logger->info("Order reservation has been processed", ['order' => $order->id]);
        $this->eventBus->dispatch(new ReservationHasBeenMadeEvent($order->id));
    }

    /**
     * @param ItemWarehouse[] $chosenWarehouses
     * @return LockInterface[]
     */
    private function createLocksForItem(array $chosenWarehouses): iterable
    {
        foreach ($chosenWarehouses as $itemWarehouse) {
            $lock = $this->warehouseItemLockingService->createLock($this->generateLockKey($itemWarehouse), 300, true);
            $lock->acquire(true);

            yield  $lock;
        }
    }

    private function getMostProfitableOrderToProcess(): ?Order
    {
        return $this->handle(new GetMostProfitableOrderQuery());
    }

    /**
     * @return ItemWarehouse[]
     */
    private function getWarehousesForItemReservation(int $itemId, int $qty): array
    {
        return $this->handle(new GetWarehousesForItemReservationQuery($itemId, $qty));
    }

    /**
     * @param LockInterface[] $itemLocks
     */
    private function releaseLocks(iterable $itemLocks): void
    {
        foreach ($itemLocks as $lock) {
            $lock->release();
        }
    }

    private function deleteReservationInWarehouses(array $chosenWarehouses): void
    {
        foreach ($chosenWarehouses as $itemWarehouse) {
                $this->commandBus->dispatch(new RemoveReservationFromWarehouseCommand($itemWarehouse->id, $itemWarehouse->itemId));
        }
    }

    private function generateLockKey(ItemWarehouse $itemWarehouse): string
    {
        return sprintf("%c-%c", $itemWarehouse->id, $itemWarehouse->itemId);
    }

    private function handle(object $message): mixed
    {
        if (!isset($this->queryBus)) {
            throw new LogicException(sprintf('You must provide a "%s" instance in the "%s::$messageBus" property, but that property has not been initialized yet.', MessageBusInterface::class, static::class));
        }

        $envelope = $this->queryBus->dispatch($message);
        /** @var HandledStamp[] $handledStamps */
        $handledStamps = $envelope->all(HandledStamp::class);

        if (!$handledStamps) {
            throw new LogicException(sprintf('Message of type "%s" was handled zero times. Exactly one handler is expected when using "%s::%s()".', get_debug_type($envelope->getMessage()), static::class, __FUNCTION__));
        }

        if (\count($handledStamps) > 1) {
            $handlers = implode(', ', array_map(function (HandledStamp $stamp): string {
                return sprintf('"%s"', $stamp->getHandlerName());
            }, $handledStamps));

            throw new LogicException(sprintf('Message of type "%s" was handled multiple times. Only one handler is expected when using "%s::%s()", got %d: %s.', get_debug_type($envelope->getMessage()), static::class, __FUNCTION__, \count($handledStamps), $handlers));
        }

        return $handledStamps[0]->getResult();
    }

    /**
     * @param ItemWarehouse[] $chosenWarehouses
     * @param LockInterface[] $itemLocks
     */
    private function revertTransation(Order $order, \RuntimeException $exception, iterable $chosenWarehouses, iterable $itemLocks): void
    {
        $this->logger->error("Reservation Failed", ['error' => $exception->getMessage(), 'order' => $order->id]);
        $this->deleteReservationInWarehouses($chosenWarehouses);
        $this->releaseLocks($itemLocks);
    }
}