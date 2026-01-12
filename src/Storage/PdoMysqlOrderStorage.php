<?php

declare(strict_types=1);

namespace OrderFlex\Storage;

use OrderFlex\Contracts\OrderStorageInterface;
use OrderFlex\OrderPage;
use OrderFlex\OrderQuery;
use OrderFlex\OrderRecord;
use PDO;
use PDOStatement;

class PdoMysqlOrderStorage implements OrderStorageInterface
{
    private PDO $pdo;
    private string $ordersTable;
    private string $relationsTable;
    private string $indexedTable;

    public function __construct(PDO $pdo, array $options = [])
    {
        $this->pdo = $pdo;
        $this->ordersTable = $options['orders_table'] ?? 'orders';
        $this->relationsTable = $options['relations_table'] ?? 'order_relations';
        $this->indexedTable = $options['indexed_table'] ?? 'order_indexed_fields';
    }

    public function save(OrderRecord $record): OrderRecord
    {
        $payload = $record->toArray();
        $now = (new \DateTimeImmutable('now'))->format('Y-m-d H:i:s');

        if ($record->getId() === null) {
            $sql = sprintf(
                'INSERT INTO %s (order_no, type, status, amount, currency, buyer_id, attributes_json, relations_json, indexed_fields_json, created_at, updated_at)
                 VALUES (:order_no, :type, :status, :amount, :currency, :buyer_id, :attributes_json, :relations_json, :indexed_fields_json, :created_at, :updated_at)',
                $this->ordersTable
            );
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([
                ':order_no' => $payload['order_no'],
                ':type' => $payload['type'],
                ':status' => $payload['status'],
                ':amount' => $payload['amount'],
                ':currency' => $payload['currency'],
                ':buyer_id' => $payload['buyer_id'],
                ':attributes_json' => $this->encodeJson($payload['attributes']),
                ':relations_json' => $this->encodeJson($payload['relations']),
                ':indexed_fields_json' => $this->encodeJson($payload['indexed_fields']),
                ':created_at' => $payload['created_at'],
                ':updated_at' => $now,
            ]);
            $id = (int) $this->pdo->lastInsertId();
            $record = $record->withId($id)->withUpdatedAt(new \DateTimeImmutable($now));
        } else {
            $sql = sprintf(
                'UPDATE %s SET type = :type, status = :status, amount = :amount, currency = :currency, buyer_id = :buyer_id,
                 attributes_json = :attributes_json, relations_json = :relations_json, indexed_fields_json = :indexed_fields_json, updated_at = :updated_at
                 WHERE id = :id',
                $this->ordersTable
            );
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([
                ':type' => $payload['type'],
                ':status' => $payload['status'],
                ':amount' => $payload['amount'],
                ':currency' => $payload['currency'],
                ':buyer_id' => $payload['buyer_id'],
                ':attributes_json' => $this->encodeJson($payload['attributes']),
                ':relations_json' => $this->encodeJson($payload['relations']),
                ':indexed_fields_json' => $this->encodeJson($payload['indexed_fields']),
                ':updated_at' => $now,
                ':id' => $record->getId(),
            ]);
            $record = $record->withUpdatedAt(new \DateTimeImmutable($now));
            $id = $record->getId();
        }

        $this->replaceRelations($id, $record->getRelations());
        $this->replaceIndexedFields($id, $record->getIndexedFields());

        return $record;
    }

    public function findById($id): ?OrderRecord
    {
        $sql = sprintf('SELECT * FROM %s WHERE id = :id', $this->ordersTable);
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ? $this->mapRow($row) : null;
    }

    public function findByOrderNo(string $orderNo): ?OrderRecord
    {
        $sql = sprintf('SELECT * FROM %s WHERE order_no = :order_no', $this->ordersTable);
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':order_no' => $orderNo]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ? $this->mapRow($row) : null;
    }

    public function query(OrderQuery $query): OrderPage
    {
        $where = [];
        $params = [];

        if ($query->getTypes()) {
            $where[] = $this->buildInClause('type', $query->getTypes(), $params, 'type');
        }

        if ($query->getStatuses()) {
            $where[] = $this->buildInClause('status', $query->getStatuses(), $params, 'status');
        }

        if ($query->getBuyerId() !== null) {
            $where[] = 'buyer_id = :buyer_id';
            $params[':buyer_id'] = $query->getBuyerId();
        } elseif ($query->getBuyerIds()) {
            $where[] = $this->buildInClause('buyer_id', $query->getBuyerIds(), $params, 'buyer');
        }

        if ($query->getOrderNo()) {
            $where[] = 'order_no = :order_no';
            $params[':order_no'] = $query->getOrderNo();
        } elseif ($query->getOrderNoLike()) {
            $where[] = 'order_no LIKE :order_no_like';
            $params[':order_no_like'] = $query->getOrderNoLike();
        }

        if ($query->getKeyword()) {
            $where[] = '(order_no LIKE :keyword OR attributes_json LIKE :keyword)';
            $params[':keyword'] = $query->getKeyword();
        }

        if ($query->getCreatedFrom()) {
            $where[] = 'created_at >= :created_from';
            $params[':created_from'] = $query->getCreatedFrom()->format('Y-m-d H:i:s');
        }

        if ($query->getCreatedTo()) {
            $where[] = 'created_at <= :created_to';
            $params[':created_to'] = $query->getCreatedTo()->format('Y-m-d H:i:s');
        }

        if ($query->getUpdatedFrom()) {
            $where[] = 'updated_at >= :updated_from';
            $params[':updated_from'] = $query->getUpdatedFrom()->format('Y-m-d H:i:s');
        }

        if ($query->getUpdatedTo()) {
            $where[] = 'updated_at <= :updated_to';
            $params[':updated_to'] = $query->getUpdatedTo()->format('Y-m-d H:i:s');
        }

        if ($query->getAmountMin() !== null) {
            $where[] = 'amount >= :amount_min';
            $params[':amount_min'] = $query->getAmountMin();
        }

        if ($query->getAmountMax() !== null) {
            $where[] = 'amount <= :amount_max';
            $params[':amount_max'] = $query->getAmountMax();
        }

        $this->appendKeyValueFilters(
            $where,
            $params,
            $query->getRelations(),
            $this->relationsTable,
            'rel'
        );
        $this->appendKeyValueFilters(
            $where,
            $params,
            $query->getIndexedFields(),
            $this->indexedTable,
            'idx'
        );

        $orderBy = $this->sanitizeOrderBy($query->getOrderBy());
        $direction = $query->getOrderDirection() === 'asc' ? 'ASC' : 'DESC';
        $whereSql = $where ? ('WHERE ' . implode(' AND ', $where)) : '';

        $sql = sprintf(
            'SELECT * FROM %s %s ORDER BY %s %s LIMIT :limit OFFSET :offset',
            $this->ordersTable,
            $whereSql,
            $orderBy,
            $direction
        );

        $stmt = $this->pdo->prepare($sql);
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        $stmt->bindValue(':limit', $query->getLimit(), PDO::PARAM_INT);
        $stmt->bindValue(':offset', $query->getOffset(), PDO::PARAM_INT);
        $stmt->execute();

        $items = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $items[] = $this->mapRow($row);
        }

        $total = null;
        if ($query->shouldWithTotal()) {
            $countSql = sprintf('SELECT COUNT(*) FROM %s %s', $this->ordersTable, $whereSql);
            $countStmt = $this->pdo->prepare($countSql);
            foreach ($params as $key => $value) {
                $countStmt->bindValue($key, $value);
            }
            $countStmt->execute();
            $total = (int) $countStmt->fetchColumn();
        }

        return new OrderPage($items, $total);
    }

    private function encodeJson(array $data): string
    {
        return json_encode($data, JSON_UNESCAPED_UNICODE);
    }

    /**
     * @param array<int, string> $values
     * @param array<string, mixed> $params
     */
    private function buildInClause(string $column, array $values, array &$params, string $prefix): string
    {
        $placeholders = [];
        foreach ($values as $index => $value) {
            $placeholder = ':' . $prefix . '_' . $index;
            $placeholders[] = $placeholder;
            $params[$placeholder] = $value;
        }

        return sprintf('%s IN (%s)', $column, implode(', ', $placeholders));
    }

    /**
     * @param array<string, mixed> $filters
     * @param array<string, mixed> $params
     */
    private function appendKeyValueFilters(
        array &$where,
        array &$params,
        array $filters,
        string $table,
        string $prefix
    ): void {
        $counter = 0;
        foreach ($filters as $key => $value) {
            $counter++;
            $keyPlaceholder = ':' . $prefix . '_key_' . $counter;
            $params[$keyPlaceholder] = $key;
            $valueClause = '';
            if (is_array($value)) {
                $valueClause = $this->buildInClause('kv.rel_value', array_values($value), $params, $prefix . '_val_' . $counter);
            } else {
                $valuePlaceholder = ':' . $prefix . '_val_' . $counter;
                $params[$valuePlaceholder] = $value;
                $valueClause = 'kv.rel_value = ' . $valuePlaceholder;
            }

            $where[] = sprintf(
                'EXISTS (SELECT 1 FROM %s kv WHERE kv.order_id = %s.id AND kv.rel_key = %s AND %s)',
                $table,
                $this->ordersTable,
                $keyPlaceholder,
                $valueClause
            );
        }
    }

    /**
     * @param array<string, mixed> $row
     */
    private function mapRow(array $row): OrderRecord
    {
        return OrderRecord::fromArray([
            'id' => $row['id'],
            'order_no' => $row['order_no'],
            'type' => $row['type'],
            'status' => $row['status'],
            'amount' => $row['amount'],
            'currency' => $row['currency'],
            'buyer_id' => $row['buyer_id'],
            'created_at' => $row['created_at'],
            'updated_at' => $row['updated_at'],
            'attributes' => $this->decodeJson($row['attributes_json']),
            'relations' => $this->decodeJson($row['relations_json']),
            'indexed_fields' => $this->decodeJson($row['indexed_fields_json']),
        ]);
    }

    private function decodeJson(?string $payload): array
    {
        if ($payload === null || $payload === '') {
            return [];
        }

        $data = json_decode($payload, true);
        return is_array($data) ? $data : [];
    }

    /**
     * @param array<string, mixed> $relations
     */
    private function replaceRelations($orderId, array $relations): void
    {
        $this->replaceKeyValueTable($this->relationsTable, $orderId, $relations);
    }

    /**
     * @param array<string, mixed> $indexedFields
     */
    private function replaceIndexedFields($orderId, array $indexedFields): void
    {
        $this->replaceKeyValueTable($this->indexedTable, $orderId, $indexedFields);
    }

    /**
     * @param array<string, mixed> $entries
     */
    private function replaceKeyValueTable(string $table, $orderId, array $entries): void
    {
        $deleteSql = sprintf('DELETE FROM %s WHERE order_id = :order_id', $table);
        $deleteStmt = $this->pdo->prepare($deleteSql);
        $deleteStmt->execute([':order_id' => $orderId]);

        $pairs = $this->flattenKeyValue($entries);
        if (!$pairs) {
            return;
        }

        $insertSql = sprintf('INSERT INTO %s (order_id, rel_key, rel_value) VALUES (:order_id, :rel_key, :rel_value)', $table);
        $insertStmt = $this->pdo->prepare($insertSql);
        foreach ($pairs as $pair) {
            $insertStmt->execute([
                ':order_id' => $orderId,
                ':rel_key' => $pair['key'],
                ':rel_value' => $pair['value'],
            ]);
        }
    }

    /**
     * @param array<string, mixed> $entries
     * @return array<int, array{key: string, value: string}>
     */
    private function flattenKeyValue(array $entries): array
    {
        $pairs = [];
        foreach ($entries as $key => $value) {
            if (is_array($value)) {
                foreach ($value as $item) {
                    if ($item === null) {
                        continue;
                    }
                    $pairs[] = ['key' => (string) $key, 'value' => (string) $item];
                }
                continue;
            }

            if ($value === null) {
                continue;
            }

            $pairs[] = ['key' => (string) $key, 'value' => (string) $value];
        }

        return $pairs;
    }

    private function sanitizeOrderBy(string $orderBy): string
    {
        $allowed = ['created_at', 'updated_at', 'id', 'order_no', 'amount'];
        return in_array($orderBy, $allowed, true) ? $orderBy : 'created_at';
    }
}
