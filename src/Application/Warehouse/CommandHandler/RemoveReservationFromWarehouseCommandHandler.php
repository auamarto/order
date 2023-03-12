<?php

namespace App\Application\Warehouse\CommandHandler;

use App\Application\Warehouse\Command\RemoveReservationFromWarehouseCommand;
use App\Domain\Warehouse\Repository\ItemWarehouseRepositoryInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
class RemoveReservationFromWarehouseCommandHandler
{
    public function __construct(
        private readonly ItemWarehouseRepositoryInterface $itemWarehouseRepository,
    )
    {
    }

    public function __invoke(RemoveReservationFromWarehouseCommand $command): void
    {
        $this->itemWarehouseRepository->removeReservation($command->warehouseId, $command->itemId);
    }
}