<?php

namespace App\Application\Order\QueryHandler;

use App\Application\Order\Query\GetMostProfitableOrderQuery;
use App\Domain\Order\Model\Order;
use App\Domain\Order\Repository\OrderRepositoryInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
class GetMostProfitableOrderQueryHandler
{
    public function __construct(
        private readonly OrderRepositoryInterface $orderRepository,
    )
    {
    }

    public function __invoke(GetMostProfitableOrderQuery $query): ?Order
    {
        return $this->orderRepository->getMostProfitableOrder();
    }
}