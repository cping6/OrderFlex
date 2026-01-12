<?php

declare(strict_types=1);

namespace OrderFlex\Contracts;

use OrderFlex\OrderPage;
use OrderFlex\OrderQuery;
use OrderFlex\OrderRecord;

interface OrderRepositoryInterface
{
    public function save(OrderRecord $record): OrderRecord;

    public function findById($id): ?OrderRecord;

    public function findByOrderNo(string $orderNo): ?OrderRecord;

    public function query(OrderQuery $query): OrderPage;
}
