<?php

namespace App\Application\Order\EventHandler;

use App\Application\Reservation\Event\ReservationHasBeenMadeEvent;
use App\Domain\Order\Repository\OrderRepositoryInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
class ReservationHasBeenMadeEventHandler
{
    public function __construct(
        private readonly OrderRepositoryInterface $orderRepository,
    )
    {
    }

    public function __invoke(ReservationHasBeenMadeEvent $reservationHasBeenMadeEvent): void
    {
        $this->orderRepository->reserve($reservationHasBeenMadeEvent->orderId);
    }
}