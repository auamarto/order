<?php

namespace App\Tests\Application\Warehouse\QueryHandler;

use App\Application\Warehouse\Query\GetWarehousesForItemReservationQuery;
use App\Application\Warehouse\QueryHandler\GetWarehousesForItemReservationQueryHandler;
use App\Domain\Order\Model\Item;
use App\Domain\Warehouse\Model\ItemWarehouse;
use App\Domain\Warehouse\Repository\ItemWarehouseRepositoryInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class GetWarehousesForItemReservationQueryHandlerTest extends TestCase
{
    private MockObject|ItemWarehouseRepositoryInterface $repository;

    public function setUp(): void
    {
        $this->repository = $this->createMock(ItemWarehouseRepositoryInterface::class);
    }

    /**
     * data contains ItemWarehouses from db in order that sql should return so Priority is considered
     * assume that wh id:1 have the highest priority
     */
    public function itemWarehouseProvider(): array
    {
        return [
            '#0 Simple order with one item that needs to be shipped from 2 warehouses' => [
                'expected' => [
                    new ItemWarehouse(1, 1, 10),
                    new ItemWarehouse(2, 1, 10),
                ],
                'orderItems' => [
                    new Item(1, 20),
                ],
                'data' => [
                    1 => [
                        new ItemWarehouse(1, 1, 10),
                        new ItemWarehouse(2, 1, 10),
                    ],
                ],
            ],
            '#1 Order with 3 items from 2 different warehouses' => [
                'expected' => [
                    new ItemWarehouse(1, 1, 10),
                    new ItemWarehouse(2, 1, 10),
                    new ItemWarehouse(2, 3, 2),
                    new ItemWarehouse(2, 2, 1),
                ],
                'orderItems' => [
                    new Item(1, 20),
                    new Item(2, 1),
                    new Item(3, 2),
                ],
                'data' => [
                    1 => [
                        new ItemWarehouse(1, 1, 10),
                        new ItemWarehouse(2, 1, 10),
                    ],
                    2 => [
                        new ItemWarehouse(2, 2, 1),
                    ],
                    3 => [
                        new ItemWarehouse(2, 3, 2),
                    ],
                ],
            ],
            '#2 Order with 3 items from 2 different warehouses but there is more wh for item 2' => [
                'expected' => [
                    new ItemWarehouse(1, 1, 10),
                    new ItemWarehouse(2, 1, 10),
                    new ItemWarehouse(2, 3, 2),
                    new ItemWarehouse(2, 2, 1),
                ],
                'orderItems' => [
                    new Item(1, 20),
                    new Item(2, 1),
                    new Item(3, 2),
                ],
                'data' => [
                    1 => [
                        new ItemWarehouse(1, 1, 10),
                        new ItemWarehouse(2, 1, 10),
                    ],
                    2 => [
                        new ItemWarehouse(2, 2, 1),
                        new ItemWarehouse(3, 2, 1),
                    ],
                    3 => [
                        new ItemWarehouse(2, 3, 2),
                    ],
                ],
            ],
            '#3 Order with 3 items but there is not enough of item 3' => [
                'expected' => [
                    new ItemWarehouse(1, 1, 10),
                    new ItemWarehouse(2, 1, 10),
                    new ItemWarehouse(2, 3, 2),
                    new ItemWarehouse(2, 2, 1),
                ],
                'orderItems' => [
                    new Item(1, 20),
                    new Item(2, 1),
                    new Item(3, 2),
                ],
                'data' => [
                    1 => [
                        new ItemWarehouse(1, 1, 10),
                        new ItemWarehouse(2, 1, 10),
                    ],
                    2 => [
                        new ItemWarehouse(2, 2, 1),
                        new ItemWarehouse(3, 2, 1),
                    ],
                    3 => [
                        new ItemWarehouse(2, 3, 1),
                    ],
                ],
                'expectException' => \RuntimeException::class
            ],
            '#4 Edge case where second wh can send whole item THIS WILL FAIL DUE TO PRIORITY ORDER' => [
                'expected' => [
                    new ItemWarehouse(2, 1, 20),
                ],
                'orderItems' => [
                    new Item(1, 20),
                ],
                'data' => [
                    1 => [
                        new ItemWarehouse(1, 1, 10),
                        new ItemWarehouse(2, 1, 20),
                    ],
                ],
            ],
        ];
    }

    /**
     * @dataProvider itemWarehouseProvider
     */
    public function testMinimalNumberOfWarehousesUsedByOrder($expected, $orderItems, $data, $exception = null): void
    {
        if (null !== $exception) {
            $this->expectException($exception);
        }

        $this->repository->expects($this->atLeastOnce())
            ->method('getWarehousesForItem')
            ->will(
                $this->returnCallback(function ($itemId) use ($data) {
                    return $data[$itemId];
                }
                )
            );

        if (\count($orderItems) > 1) {
            $this->repository->expects($this->atMost(count($orderItems)-1))
                ->method('getFromInvolvedWhs')
                ->will(
                    $this->returnCallback(function ($itemId) use ($data) {
                        return $data[$itemId];
                    }
                    )
                );
        }

        $handler = new GetWarehousesForItemReservationQueryHandler($this->repository);

        $actual = $handler->__invoke(new GetWarehousesForItemReservationQuery($orderItems));
        $this->assertEquals($expected, $actual);
    }
}
