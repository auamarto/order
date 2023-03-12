<?php

namespace App\Domain\Order\Model;

class Order
{
    /**
     * @param Item[] $items
     */
    public function __construct(
        public readonly int $id = 0,
        private readonly array $items = [],
    )
    {
    }

    /**
     * @return Item[]
     */
    public function getItems(): array
    {
        return $this->items;
    }
}