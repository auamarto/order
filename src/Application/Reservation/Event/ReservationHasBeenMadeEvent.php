<?php

namespace App\Application\Reservation\Event;

class ReservationHasBeenMadeEvent
{
    public function __construct(
        public readonly int $orderId,
    )
    {
    }
}