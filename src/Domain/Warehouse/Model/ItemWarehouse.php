<?php

namespace App\Domain\Warehouse\Model;

class ItemWarehouse
{
    public function __construct(
        public readonly int $id,
        public readonly int $itemId,
        public readonly int $qty,
    ) {}
}