# OrderFlex

OrderFlex 是一个适用于多种 PHP 框架的订单记录扩展包，提供统一的订单基础字段，同时允许业务自定义字段灵活扩展，并通过索引型的键值表实现快速查询。

## 安装

```bash
composer require acme/order-flex
```

## 设计说明

订单数据分为三部分：

- `orders`：统一的基础字段（订单号、类型、状态、金额、买家、时间等）。
- `attributes`：灵活扩展字段，存储 JSON，不直接索引。
- `relations` / `indexed_fields`：用于快速查询的键值表，适合外部关联字段或需要高频筛选的字段。

你可以自由定义订单类型，例如：正常订单、拼团、营销、积分商城、服务订单、秒杀、统筹、代付、充值、VIP、套餐等。

## 字段说明

基础字段（`orders` 表）：

- `id`：自增主键。
- `order_no`：订单号（唯一）。
- `type`：订单类型（如 normal、group、marketing、points、service、seckill 等）。
- `status`：订单状态（如 pending、paid、shipped、finished、closed 等）。
- `amount`：订单实际支付金额。
- `currency`：币种（默认 CNY）。
- `buyer_id`：买家 ID（可用作用户筛选）。
- `attributes_json`：扩展字段 JSON（展示用，不建索引）。
- `relations_json`：关联字段 JSON（展示用）。
- `indexed_fields_json`：索引字段 JSON（展示用）。
- `created_at` / `updated_at`：创建/更新时间。

扩展字段（逻辑层）：

- `attributes`：展示用字段，适合存放订单详情、营销明细、积分、备注等。
- `relations`：跨系统关联字段（如 user_id、shop_id、app_id、order_source）。
- `indexed_fields`：高频筛选字段（如 campaign_id、coupon_id、delivery_type）。

## 数据表

参考并执行 `migrations/mysql.sql` 创建表结构。

## 快速开始

```php
<?php

use OrderFlex\OrderQuery;
use OrderFlex\OrderRecord;
use OrderFlex\Repository\OrderRepository;
use OrderFlex\Storage\PdoMysqlOrderStorage;

$pdo = new PDO('mysql:host=127.0.0.1;dbname=app', 'user', 'pass');
$storage = new PdoMysqlOrderStorage($pdo);
$repo = new OrderRepository($storage);

// 1) 创建订单
$record = OrderRecord::fromArray([
    'order_no' => 'NO20250101001',
    'type' => 'group',
    'status' => 'paid',
    'amount' => 199.00,
    'currency' => 'CNY',
    'buyer_id' => 'u_1001',
    'attributes' => [
        'sku_id' => 'sku_1',
        'coupon_id' => 'c_9',
        'note' => 'extra fields',
    ],
    'relations' => [
        'user_id' => 'u_1001',
        'shop_id' => 's_20',
    ],
    'indexed_fields' => [
        'campaign_id' => 'mkt_88',
        'delivery_type' => 'express',
    ],
    'created_at' => '2025-01-01 10:00:00',
    'updated_at' => '2025-01-01 10:00:00',
]);

$saved = $repo->save($record);

// 2) 查询订单
$query = (new OrderQuery())
    ->withType('group')
    ->withStatuses(['paid', 'shipped'])
    ->withBuyerIds(['u_1001', 'u_1002'])
    ->withRelations(['shop_id' => 's_20'])
    ->withIndexedFields(['campaign_id' => 'mkt_88'])
    ->withAmountBetween(100, 500)
    ->withOrderNoLike('NO2025', true)
    ->withKeyword('sku_1')
    ->withCreatedBetween('2025-01-01 00:00:00', '2025-01-31 23:59:59')
    ->withUpdatedBetween('2025-01-01 00:00:00', '2025-01-31 23:59:59')
    ->withLimit(20)
    ->withOffset(0);

$page = $repo->query($query);
```

## 常见查询示例

### 按用户查询订单

```php
$query = (new OrderQuery())
    ->withBuyerId('u_1001')
    ->withLimit(20)
    ->withOffset(0);
```

如果你的应用用户字段不是 `buyer_id`，可把用户 ID 放到 `relations`：

```php
$query = (new OrderQuery())
    ->withRelations(['user_id' => 'u_1001']);
```

### 按类型 / 状态查询

```php
$query = (new OrderQuery())
    ->withType('seckill')
    ->withStatus('paid');
```

### 按时间区间查询

```php
$query = (new OrderQuery())
    ->withCreatedBetween('2025-01-01 00:00:00', '2025-01-31 23:59:59');
```

### 按关键词查询

关键词默认匹配 `order_no` 与 `attributes_json` 内容：

```php
$query = (new OrderQuery())
    ->withKeyword('sku_1');
```

## 字段建议

- `attributes`：用于展示字段（不常用于筛选的扩展字段）。
- `relations`：跨系统关联字段（例如 user_id、shop_id、app_id 等），支持多个字段查询。
- `indexed_fields`：高频查询字段（例如 campaign_id、delivery_type），用于提升查询性能。

## 汇总与榜单

为了支持快速汇总与榜单查询，建议将需要统计的维度写入 `indexed_fields`，例如：

- `shop_id`、`campaign_id`、`source`、`pay_channel`、`order_type`

然后在业务侧执行聚合 SQL：

### 订单汇总（按类型/状态/时间）

```sql
SELECT type, status, COUNT(*) AS cnt, SUM(amount) AS total_amount
FROM orders
WHERE created_at BETWEEN :from AND :to
GROUP BY type, status;
```

### 营销活动汇总（按活动维度）

```sql
SELECT kv.rel_value AS campaign_id, COUNT(*) AS cnt, SUM(o.amount) AS total_amount
FROM orders o
JOIN order_indexed_fields kv ON kv.order_id = o.id
WHERE kv.rel_key = 'campaign_id'
  AND o.created_at BETWEEN :from AND :to
GROUP BY kv.rel_value
ORDER BY total_amount DESC;
```

### 榜单（按用户/店铺/活动排行）

```sql
SELECT kv.rel_value AS user_id, COUNT(*) AS cnt, SUM(o.amount) AS total_amount
FROM orders o
JOIN order_indexed_fields kv ON kv.order_id = o.id
WHERE kv.rel_key = 'user_id'
  AND o.created_at BETWEEN :from AND :to
GROUP BY kv.rel_value
ORDER BY total_amount DESC
LIMIT 20;
```

如果需要更高性能的榜单，可以在业务侧建立“每日汇总表”或使用离线任务，将结果写入独立统计表。

## 适配多框架的方式

本包只依赖 PDO 与纯 PHP 类，支持在 Laravel、Symfony、ThinkPHP、Yii 等框架中使用。你可以：

- 直接实例化 `OrderRepository`；
- 在框架容器中注册 `OrderStorageInterface`；
- 或自定义存储实现以适配你的数据库或 ORM。
