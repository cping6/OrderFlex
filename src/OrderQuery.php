<?php

declare(strict_types=1);

namespace OrderFlex;

use DateTimeImmutable;
use DateTimeInterface;

class OrderQuery
{
    /** @var array<int, string> */
    private array $types = [];

    /** @var array<int, string> */
    private array $statuses = [];

    /** @var int|string|null */
    private $buyerId;

    /** @var array<int, int|string> */
    private array $buyerIds = [];

    private ?string $orderNo = null;
    private ?string $orderNoLike = null;
    private ?string $keyword = null;

    /** @var array<string, mixed> */
    private array $relations = [];

    /** @var array<string, mixed> */
    private array $indexedFields = [];

    private ?DateTimeImmutable $createdFrom = null;
    private ?DateTimeImmutable $createdTo = null;
    private ?DateTimeImmutable $updatedFrom = null;
    private ?DateTimeImmutable $updatedTo = null;

    /** @var int|float|string|null */
    private $amountMin;

    /** @var int|float|string|null */
    private $amountMax;

    private int $limit = 20;
    private int $offset = 0;
    private string $orderBy = 'created_at';
    private string $orderDirection = 'desc';
    private bool $withTotal = true;

    /**
     * @param array<int, string> $types
     */
    public function withTypes(array $types): self
    {
        $clone = clone $this;
        $clone->types = array_values($types);
        return $clone;
    }

    public function withType(string $type): self
    {
        return $this->withTypes([$type]);
    }

    /**
     * @param array<int, string> $statuses
     */
    public function withStatuses(array $statuses): self
    {
        $clone = clone $this;
        $clone->statuses = array_values($statuses);
        return $clone;
    }

    public function withStatus(string $status): self
    {
        return $this->withStatuses([$status]);
    }

    /**
     * @param int|string|null $buyerId
     */
    public function withBuyerId($buyerId): self
    {
        $clone = clone $this;
        $clone->buyerId = $buyerId;
        return $clone;
    }

    /**
     * @param array<int, int|string> $buyerIds
     */
    public function withBuyerIds(array $buyerIds): self
    {
        $clone = clone $this;
        $clone->buyerIds = array_values($buyerIds);
        return $clone;
    }

    public function withOrderNo(string $orderNo): self
    {
        $clone = clone $this;
        $clone->orderNo = $orderNo;
        return $clone;
    }

    public function withOrderNoLike(string $keyword, bool $prefixOnly = true): self
    {
        $clone = clone $this;
        $keyword = trim($keyword);
        if ($keyword === '') {
            $clone->orderNoLike = null;
            return $clone;
        }

        $clone->orderNoLike = $prefixOnly ? ($keyword . '%') : ('%' . $keyword . '%');
        return $clone;
    }

    public function withKeyword(string $keyword): self
    {
        $clone = clone $this;
        $keyword = trim($keyword);
        if ($keyword === '') {
            $clone->keyword = null;
            return $clone;
        }

        $clone->keyword = '%' . $keyword . '%';
        return $clone;
    }

    /**
     * @param array<string, mixed> $relations
     */
    public function withRelations(array $relations): self
    {
        $clone = clone $this;
        $clone->relations = $relations;
        return $clone;
    }

    /**
     * @param array<string, mixed> $indexedFields
     */
    public function withIndexedFields(array $indexedFields): self
    {
        $clone = clone $this;
        $clone->indexedFields = $indexedFields;
        return $clone;
    }

    public function withCreatedBetween($from, $to): self
    {
        $clone = clone $this;
        $clone->createdFrom = $from ? $this->toDateTime($from) : null;
        $clone->createdTo = $to ? $this->toDateTime($to) : null;
        return $clone;
    }

    public function withCreatedAfter($from): self
    {
        return $this->withCreatedBetween($from, $this->createdTo);
    }

    public function withCreatedBefore($to): self
    {
        return $this->withCreatedBetween($this->createdFrom, $to);
    }

    public function withUpdatedBetween($from, $to): self
    {
        $clone = clone $this;
        $clone->updatedFrom = $from ? $this->toDateTime($from) : null;
        $clone->updatedTo = $to ? $this->toDateTime($to) : null;
        return $clone;
    }

    public function withUpdatedAfter($from): self
    {
        return $this->withUpdatedBetween($from, $this->updatedTo);
    }

    public function withUpdatedBefore($to): self
    {
        return $this->withUpdatedBetween($this->updatedFrom, $to);
    }

    public function withAmountBetween($min, $max): self
    {
        $clone = clone $this;
        $clone->amountMin = $min;
        $clone->amountMax = $max;
        return $clone;
    }

    public function withLimit(int $limit): self
    {
        $clone = clone $this;
        $clone->limit = max(1, $limit);
        return $clone;
    }

    public function withOffset(int $offset): self
    {
        $clone = clone $this;
        $clone->offset = max(0, $offset);
        return $clone;
    }

    public function orderBy(string $column, string $direction = 'desc'): self
    {
        $clone = clone $this;
        $clone->orderBy = $column;
        $clone->orderDirection = strtolower($direction) === 'asc' ? 'asc' : 'desc';
        return $clone;
    }

    public function withTotal(bool $withTotal): self
    {
        $clone = clone $this;
        $clone->withTotal = $withTotal;
        return $clone;
    }

    /**
     * @return array<int, string>
     */
    public function getTypes(): array
    {
        return $this->types;
    }

    /**
     * @return array<int, string>
     */
    public function getStatuses(): array
    {
        return $this->statuses;
    }

    public function getBuyerId()
    {
        return $this->buyerId;
    }

    public function getOrderNo(): ?string
    {
        return $this->orderNo;
    }

    public function getOrderNoLike(): ?string
    {
        return $this->orderNoLike;
    }

    public function getKeyword(): ?string
    {
        return $this->keyword;
    }

    /**
     * @return array<string, mixed>
     */
    public function getRelations(): array
    {
        return $this->relations;
    }

    /**
     * @return array<string, mixed>
     */
    public function getIndexedFields(): array
    {
        return $this->indexedFields;
    }

    public function getCreatedFrom(): ?DateTimeImmutable
    {
        return $this->createdFrom;
    }

    public function getCreatedTo(): ?DateTimeImmutable
    {
        return $this->createdTo;
    }

    public function getUpdatedFrom(): ?DateTimeImmutable
    {
        return $this->updatedFrom;
    }

    public function getUpdatedTo(): ?DateTimeImmutable
    {
        return $this->updatedTo;
    }

    public function getAmountMin()
    {
        return $this->amountMin;
    }

    public function getAmountMax()
    {
        return $this->amountMax;
    }

    /**
     * @return array<int, int|string>
     */
    public function getBuyerIds(): array
    {
        return $this->buyerIds;
    }

    public function getLimit(): int
    {
        return $this->limit;
    }

    public function getOffset(): int
    {
        return $this->offset;
    }

    public function getOrderBy(): string
    {
        return $this->orderBy;
    }

    public function getOrderDirection(): string
    {
        return $this->orderDirection;
    }

    public function shouldWithTotal(): bool
    {
        return $this->withTotal;
    }

    private function toDateTime($value): DateTimeImmutable
    {
        if ($value instanceof DateTimeImmutable) {
            return $value;
        }

        if ($value instanceof DateTimeInterface) {
            return DateTimeImmutable::createFromInterface($value);
        }

        return new DateTimeImmutable((string) $value);
    }
}
