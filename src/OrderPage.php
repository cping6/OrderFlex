<?php

declare(strict_types=1);

namespace OrderFlex;

class OrderPage
{
    /** @var array<int, OrderRecord> */
    private array $items;

    private ?int $total;

    /**
     * @param array<int, OrderRecord> $items
     */
    public function __construct(array $items, ?int $total)
    {
        $this->items = $items;
        $this->total = $total;
    }

    /**
     * @return array<int, OrderRecord>
     */
    public function getItems(): array
    {
        return $this->items;
    }

    public function getTotal(): ?int
    {
        return $this->total;
    }
}
