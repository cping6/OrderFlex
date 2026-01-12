<?php

declare(strict_types=1);

namespace OrderFlex\Repository;

use OrderFlex\Contracts\OrderRepositoryInterface;
use OrderFlex\Contracts\OrderStorageInterface;
use OrderFlex\OrderPage;
use OrderFlex\OrderQuery;
use OrderFlex\OrderRecord;

class OrderRepository implements OrderRepositoryInterface
{
    private OrderStorageInterface $storage;

    public function __construct(OrderStorageInterface $storage)
    {
        $this->storage = $storage;
    }

    public function save(OrderRecord $record): OrderRecord
    {
        return $this->storage->save($record);
    }

    public function findById($id): ?OrderRecord
    {
        return $this->storage->findById($id);
    }

    public function findByOrderNo(string $orderNo): ?OrderRecord
    {
        return $this->storage->findByOrderNo($orderNo);
    }

    public function query(OrderQuery $query): OrderPage
    {
        return $this->storage->query($query);
    }
}
