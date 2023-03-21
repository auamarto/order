<?php

namespace App\Infrastructure\Warehouse\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
class Item
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    public int $id;

    #[ORM\Column]
    public int $qty;

    #[ORM\ManyToOne(targetEntity: Warehouse::class, inversedBy: 'items')]
    public $warehouse;

    #[ORM\OneToMany(targetEntity: Reservation::class, mappedBy: 'item')]
    public $reservations;

    public function __construct()
    {
        $this->reservations = new ArrayCollection();
    }

    /**
     * @return int
     */
    public function getQty(): int
    {
        return $this->qty - array_reduce($this->reservations, function (int $carry, Reservation $reservation) {
            $carry += $reservation->qty;

            return $carry;
            });
    }
}