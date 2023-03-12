<?php

namespace App\Tests\Application\Reservation\EventHandler;

use App\Application\Order\Event\OrderCreatedEvent;
use App\Application\Order\Query\GetMostProfitableOrderQuery;
use App\Application\Reservation\Event\ReservationHasBeenMadeEvent;
use App\Application\Reservation\EventHandler\OrderCreatedEventHandler;
use App\Application\Reservation\Exception\RequiredQtyHasNotBeenReservedException;
use App\Application\Warehouse\Command\MakeItemReservationInWarehouseCommand;
use App\Application\Warehouse\Command\RemoveReservationFromWarehouseCommand;
use App\Application\Warehouse\Query\GetWarehousesForItemReservationQuery;
use App\Domain\Order\Model\Item;
use App\Domain\Order\Model\Order;
use App\Domain\Reservation\Repository\ReservationRepositoryInterface;
use App\Domain\Warehouse\Model\ItemWarehouse;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\Lock\Store\InMemoryStore;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Stamp\HandledStamp;

class OrderCreatedEventHandlerTest extends TestCase
{
    private MockObject|MessageBusInterface $queryBus;

    private MockObject|MessageBusInterface $commandBus;

    private MockObject|MessageBusInterface $eventBus;

    private InMemoryStore $store;

    public function setUp(): void
    {
        $this->queryBus = $this->createMock(MessageBusInterface::class);
        $this->commandBus = $this->createMock(MessageBusInterface::class);
        $this->eventBus = $this->createMock(MessageBusInterface::class);
        $this->store = new InMemoryStore();
        $this->reservationRepository = $this->createMock(ReservationRepositoryInterface::class);
        $this->logger = $this->createMock(LoggerInterface::class);
    }

    public function testHandlerWhenNoOrderToProcess(): void
    {
        $this->queryBus->expects($this->once())
            ->method('dispatch')
            ->with(new GetMostProfitableOrderQuery())
            ->willReturn(new Envelope((object)[], [new HandledStamp(null, 'HandlerName')]));

        $this->logger->expects($this->once())->method('warning');

        $handler = new OrderCreatedEventHandler(
            $this->queryBus,
            $this->commandBus,
            $this->eventBus,
            $this->store,
            $this->reservationRepository,
            $this->logger
        );

        $handler->__invoke(new OrderCreatedEvent(new Order()));
    }

    public function testHandlerWhenNoItemsInOrder(): void
    {
        $order = new Order(1, []);

        $this->queryBus->expects($this->once())
            ->method('dispatch')
            ->with(new GetMostProfitableOrderQuery())
            ->willReturn(new Envelope((object)[], [new HandledStamp($order, 'HandlerName')]));

        $this->logger->expects($this->exactly(2))->method('info');

        $this->eventBus->expects($this->once())
            ->method('dispatch')
            ->with(new ReservationHasBeenMadeEvent($order->id))
            ->willReturn(new Envelope((object)[]));

        $handler = new OrderCreatedEventHandler(
            $this->queryBus,
            $this->commandBus,
            $this->eventBus,
            $this->store,
            $this->reservationRepository,
            $this->logger
        );

        $handler->__invoke(new OrderCreatedEvent(new Order()));
    }

    public function testWhenAllItemsAvailableToReserve(): void
    {
        $item = new Item(1, 10);
        $order = new Order(1, [$item]);

        $itemWarehouse1 = new ItemWarehouse(99, 1, 5);
        $itemWarehouse2 = new ItemWarehouse(13, 1, 5);

        $this->queryBus->expects($this->exactly(2))
            ->method('dispatch')
            ->will(
                $this->returnCallback(function ($command) use ($order, $itemWarehouse1, $itemWarehouse2) {
                    return match (get_class($command)) {
                        GetMostProfitableOrderQuery::class => new Envelope((object)[$order], [new HandledStamp($order, 'HandlerName')]),
                        GetWarehousesForItemReservationQuery::class => new Envelope((object)[$itemWarehouse1, $itemWarehouse2], [
                            new HandledStamp([
                                $itemWarehouse1,
                                $itemWarehouse2,
                            ], 'HandlerName')
                        ])
                    };
                }
                )
            );

        $this->commandBus->expects(self::exactly(2))
            ->method('dispatch')
            ->will(
                $this->returnCallback(function (MakeItemReservationInWarehouseCommand $command) {
                    return new Envelope((object)[]);
                }
                )
            );

        $this->logger->expects($this->exactly(2))->method('info');

        $this->eventBus->expects($this->once())
            ->method('dispatch')
            ->with(new ReservationHasBeenMadeEvent($order->id))
            ->willReturn(new Envelope((object)[]));

        $handler = new OrderCreatedEventHandler(
            $this->queryBus,
            $this->commandBus,
            $this->eventBus,
            $this->store,
            $this->reservationRepository,
            $this->logger
        );

        $handler->__invoke(new OrderCreatedEvent(new Order()));
    }

    public function testWhenOneItemIsNotAvailableForReservation(): void
    {
        $item = new Item(1, 10);
        $order = new Order(1, [$item]);

        $itemWarehouse1 = new ItemWarehouse(99, 1, 5);

        $this->queryBus->expects($this->exactly(2))
            ->method('dispatch')
            ->will(
                $this->returnCallback(function ($command) use ($order, $itemWarehouse1) {
                    return match (get_class($command)) {
                        GetMostProfitableOrderQuery::class => new Envelope((object)[$order], [new HandledStamp($order, 'HandlerName')]),
                        GetWarehousesForItemReservationQuery::class => new Envelope((object)[$itemWarehouse1], [
                            new HandledStamp([
                                $itemWarehouse1,
                            ], 'HandlerName')
                        ])
                    };
                }
                )
            );

        $this->commandBus->expects(self::exactly(2))
            ->method('dispatch')
            ->will(
                $this->returnCallback(function ($command) {
                    return match (get_class($command)) {
                        MakeItemReservationInWarehouseCommand::class => new Envelope((object)[]),
                        RemoveReservationFromWarehouseCommand::class => new Envelope((object)[]),
                    };
                }
                )
            );

        $this->logger->expects($this->exactly(1))->method('info');
        $this->logger->expects($this->exactly(1))->method('error');

        $this->eventBus->expects($this->never())
            ->method('dispatch');

        $handler = new OrderCreatedEventHandler(
            $this->queryBus,
            $this->commandBus,
            $this->eventBus,
            $this->store,
            $this->reservationRepository,
            $this->logger
        );

        $this->expectException(RequiredQtyHasNotBeenReservedException::class);

        $handler->__invoke(new OrderCreatedEvent(new Order()));
    }
}
