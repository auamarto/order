<?php

namespace App\Domain\Reservation\Repository;

use App\Domain\Reservation\Model\Reservation;

interface ReservationRepositoryInterface
{
    public function save(Reservation $reservation): void;
}