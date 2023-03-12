<?php

namespace App\Application\Order\Event;

use App\Domain\Order\Model\Order;

class OrderCreatedEvent
{
    public function __construct(
        public readonly Order $order,
    ) {
    }
}