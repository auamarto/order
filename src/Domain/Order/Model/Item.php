<?php

namespace App\Domain\Order\Model;

class Item
{
    public function __construct(
        public readonly int $id,
        public readonly int $qty,
    ) {}


}