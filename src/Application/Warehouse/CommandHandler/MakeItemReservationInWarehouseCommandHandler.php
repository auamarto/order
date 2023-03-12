<?php

namespace App\Application\Warehouse\CommandHandler;

use App\Application\Warehouse\Command\MakeItemReservationInWarehouseCommand;
use App\Domain\Warehouse\Exception\ItemReservationFailed;
use App\Domain\Warehouse\Repository\ItemWarehouseRepositoryInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
class MakeItemReservationInWarehouseCommandHandler
{
    public function __construct(
        private readonly ItemWarehouseRepositoryInterface $itemWarehouseRepository,
        private readonly LoggerInterface $logger,
    )
    {
    }

    public function __invoke(MakeItemReservationInWarehouseCommand $command): void
    {
        try {
            $this->itemWarehouseRepository->makeReservation($command->warehouseId, $command->itemId, $command->qty);
        } catch (ItemReservationFailed $exception) {
            $this->logger->alert($exception->getMessage(), ['command' => $command]);

            throw $exception;
        }

    }
}