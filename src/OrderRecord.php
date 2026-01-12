<?php

declare(strict_types=1);

namespace OrderFlex;

use DateTimeImmutable;
use DateTimeInterface;

class OrderRecord
{
    /** @var int|string|null */
    private $id;

    private string $orderNo;
    private string $type;
    private string $status;
    private string $currency;

    /** @var int|float|string */
    private $amount;

    /** @var int|string|null */
    private $buyerId;

    private DateTimeImmutable $createdAt;
    private DateTimeImmutable $updatedAt;

    /** @var array<string, mixed> */
    private array $attributes;

    /** @var array<string, mixed> */
    private array $relations;

    /** @var array<string, mixed> */
    private array $indexedFields;

    /**
     * @param int|string|null $id
     * @param int|float|string $amount
     * @param int|string|null $buyerId
     * @param array<string, mixed> $attributes
     * @param array<string, mixed> $relations
     * @param array<string, mixed> $indexedFields
     */
    public function __construct(
        $id,
        string $orderNo,
        string $type,
        string $status,
        $amount,
        string $currency,
        $buyerId,
        DateTimeImmutable $createdAt,
        DateTimeImmutable $updatedAt,
        array $attributes = [],
        array $relations = [],
        array $indexedFields = []
    ) {
        $this->id = $id;
        $this->orderNo = $orderNo;
        $this->type = $type;
        $this->status = $status;
        $this->amount = $amount;
        $this->currency = $currency;
        $this->buyerId = $buyerId;
        $this->createdAt = $createdAt;
        $this->updatedAt = $updatedAt;
        $this->attributes = $attributes;
        $this->relations = $relations;
        $this->indexedFields = $indexedFields;
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        $createdAt = self::toDateTime($data['created_at'] ?? 'now');
        $updatedAt = self::toDateTime($data['updated_at'] ?? 'now');

        return new self(
            $data['id'] ?? null,
            (string) $data['order_no'],
            (string) $data['type'],
            (string) $data['status'],
            $data['amount'] ?? 0,
            (string) ($data['currency'] ?? 'CNY'),
            $data['buyer_id'] ?? null,
            $createdAt,
            $updatedAt,
            (array) ($data['attributes'] ?? []),
            (array) ($data['relations'] ?? []),
            (array) ($data['indexed_fields'] ?? [])
        );
    }

    public function withId($id): self
    {
        return new self(
            $id,
            $this->orderNo,
            $this->type,
            $this->status,
            $this->amount,
            $this->currency,
            $this->buyerId,
            $this->createdAt,
            $this->updatedAt,
            $this->attributes,
            $this->relations,
            $this->indexedFields
        );
    }

    public function withUpdatedAt(DateTimeImmutable $updatedAt): self
    {
        return new self(
            $this->id,
            $this->orderNo,
            $this->type,
            $this->status,
            $this->amount,
            $this->currency,
            $this->buyerId,
            $this->createdAt,
            $updatedAt,
            $this->attributes,
            $this->relations,
            $this->indexedFields
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'order_no' => $this->orderNo,
            'type' => $this->type,
            'status' => $this->status,
            'amount' => $this->amount,
            'currency' => $this->currency,
            'buyer_id' => $this->buyerId,
            'created_at' => $this->createdAt->format('Y-m-d H:i:s'),
            'updated_at' => $this->updatedAt->format('Y-m-d H:i:s'),
            'attributes' => $this->attributes,
            'relations' => $this->relations,
            'indexed_fields' => $this->indexedFields,
        ];
    }

    public function getId()
    {
        return $this->id;
    }

    public function getOrderNo(): string
    {
        return $this->orderNo;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function getAmount()
    {
        return $this->amount;
    }

    public function getCurrency(): string
    {
        return $this->currency;
    }

    public function getBuyerId()
    {
        return $this->buyerId;
    }

    public function getCreatedAt(): DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): DateTimeImmutable
    {
        return $this->updatedAt;
    }

    /**
     * @return array<string, mixed>
     */
    public function getAttributes(): array
    {
        return $this->attributes;
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

    private static function toDateTime($value): DateTimeImmutable
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
