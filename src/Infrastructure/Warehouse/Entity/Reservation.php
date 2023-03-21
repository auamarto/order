<?php

namespace App\Infrastructure\Warehouse\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
class Reservation
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    public int $id;

    #[ORM\ManyToOne(targetEntity: Item::class, inversedBy: 'reservations')]
    public $item;

    #[ORM\Column]
    public int $qty;
}