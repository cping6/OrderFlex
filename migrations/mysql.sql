CREATE TABLE `orders` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `order_no` VARCHAR(64) NOT NULL,
  `type` VARCHAR(32) NOT NULL,
  `status` VARCHAR(32) NOT NULL,
  `amount` DECIMAL(12,2) NOT NULL DEFAULT 0,
  `currency` VARCHAR(8) NOT NULL DEFAULT 'CNY',
  `buyer_id` VARCHAR(64) NULL,
  `attributes_json` TEXT NULL,
  `relations_json` TEXT NULL,
  `indexed_fields_json` TEXT NULL,
  `created_at` DATETIME NOT NULL,
  `updated_at` DATETIME NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_order_no` (`order_no`),
  KEY `idx_type` (`type`),
  KEY `idx_status` (`status`),
  KEY `idx_buyer` (`buyer_id`),
  KEY `idx_created` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE `order_relations` (
  `order_id` BIGINT UNSIGNED NOT NULL,
  `rel_key` VARCHAR(64) NOT NULL,
  `rel_value` VARCHAR(128) NOT NULL,
  PRIMARY KEY (`order_id`, `rel_key`, `rel_value`),
  KEY `idx_rel_key_value` (`rel_key`, `rel_value`),
  KEY `idx_rel_key` (`rel_key`),
  CONSTRAINT `fk_rel_order` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE `order_indexed_fields` (
  `order_id` BIGINT UNSIGNED NOT NULL,
  `rel_key` VARCHAR(64) NOT NULL,
  `rel_value` VARCHAR(128) NOT NULL,
  PRIMARY KEY (`order_id`, `rel_key`, `rel_value`),
  KEY `idx_idx_key_value` (`rel_key`, `rel_value`),
  KEY `idx_idx_key` (`rel_key`),
  CONSTRAINT `fk_idx_order` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
