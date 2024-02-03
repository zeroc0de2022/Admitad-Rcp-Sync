-- Adminer 4.8.1 MySQL 8.0.30 dump

SET NAMES utf8;
SET time_zone = '+00:00';
SET foreign_key_checks = 0;
SET sql_mode = 'NO_AUTO_VALUE_ON_ZERO';

SET NAMES utf8mb4;

DROP TABLE IF EXISTS `access`;
CREATE TABLE `access` (
  `id` int NOT NULL AUTO_INCREMENT,
  `access` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `values` json NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `access` (`id`, `access`, `values`) VALUES
(1,	'granted',	'[\"admin\", \"superadmin\"]'),
(2,	'role',	'[\"admin\", \"banned\", \"user\"]'),
(3,	'column',	'[\"user_name\", \"user_email\", \"user_pass\", \"user_status\"]'),
(4,	'superuser',	'[\"superadmin\"]');

DROP TABLE IF EXISTS `pages`;
CREATE TABLE `pages` (
  `page_id` int NOT NULL AUTO_INCREMENT,
  `page_name` varchar(255) NOT NULL,
  `page_url` varchar(255) NOT NULL,
  `page_done` varchar(25) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NOT NULL DEFAULT 'no',
  PRIMARY KEY (`page_id`),
  UNIQUE KEY `page_url` (`page_url`),
  UNIQUE KEY `page_name` (`page_name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;

INSERT INTO `pages` (`page_id`, `page_name`, `page_url`, `page_done`) VALUES
(1,	'parser',	'parser',	'no'),
(2,	'product',	'product',	'yes'),
(3,	'proxy',	'proxy',	'yes'),
(4,	'users',	'users',	'no'),
(5,	'profile',	'profile',	'no'),
(6,	'logout',	'logout',	'yes'),
(7,	'auth',	'auth',	'yes'),
(8,	'index',	'/',	'yes'),
(9,	'verify',	'verify',	'yes'),
(10,	'api',	'api',	'no'),
(11,	'banned',	'banned',	'no'),
(12,	'pars',	'pars',	'no'),
(13,	'importer',	'importer',	'yes');

DROP TABLE IF EXISTS `product`;
CREATE TABLE `product` (
  `product_id` int NOT NULL COMMENT 'product ID',
  `tool_name` varchar(255) DEFAULT NULL COMMENT 'donor',
  `check_new` varchar(50) NOT NULL DEFAULT '1' COMMENT 'to differentiate between new and changed products',
  `available` tinyint NOT NULL DEFAULT '0' COMMENT 'availability',
  `pars_status` smallint NOT NULL DEFAULT '0' COMMENT 'pars status',
  `categoryId` varchar(255) NOT NULL COMMENT 'category',
  `currencyId` varchar(30) NOT NULL COMMENT 'currency',
  `model` varchar(255) NOT NULL COMMENT 'model',
  `modified_time` int NOT NULL COMMENT 'modified time',
  `name` varchar(255) NOT NULL COMMENT 'name',
  `picture` varchar(255) DEFAULT NULL COMMENT 'picture',
  `price` varchar(20) NOT NULL COMMENT 'price',
  `typePrefix` varchar(255) NOT NULL COMMENT 'product type',
  `url` varchar(255) NOT NULL COMMENT 'link',
  `admitad` varchar(255) NOT NULL COMMENT 'deeplink',
  `vendor` varchar(255) NOT NULL COMMENT 'vendor',
  UNIQUE KEY `id` (`product_id`),
  UNIQUE KEY `url` (`url`),
  KEY `tool_name` (`tool_name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;


DROP TABLE IF EXISTS `product_data`;
CREATE TABLE `product_data` (
  `product_id` int NOT NULL,
  `images` json NOT NULL COMMENT 'images (parser)',
  `description` text NOT NULL COMMENT 'description (parser)',
  `attrs` json NOT NULL COMMENT 'attrs (parser)',
  `reviews` json NOT NULL COMMENT 'reviews (parser)',
  UNIQUE KEY `id` (`product_id`),
  CONSTRAINT `product_data_ibfk_1` FOREIGN KEY (`product_id`) REFERENCES `product` (`product_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;


DROP TABLE IF EXISTS `proxy`;
CREATE TABLE `proxy` (
  `proxy_id` int NOT NULL AUTO_INCREMENT,
  `proxy` varchar(255) NOT NULL,
  `type` varchar(255) NOT NULL COMMENT 'proxy type',
  `notice` varchar(255) NOT NULL DEFAULT 'new',
  `status` smallint NOT NULL DEFAULT '0' COMMENT 'status proxy',
  `uptime` bigint NOT NULL,
  PRIMARY KEY (`proxy`),
  UNIQUE KEY `id` (`proxy_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;


DROP TABLE IF EXISTS `tools`;
CREATE TABLE `tools` (
  `tool_id` int NOT NULL AUTO_INCREMENT,
  `tool` varchar(255) NOT NULL COMMENT 'type ',
  `tool_name` varchar(255) NOT NULL COMMENT 'tool name',
  `remote_link` varchar(255) NOT NULL COMMENT 'remote link to csv file',
  `reg_time` varchar(255) NOT NULL COMMENT 'register time',
  PRIMARY KEY (`tool_id`),
  UNIQUE KEY `parser_name` (`tool_name`),
  UNIQUE KEY `remote_link` (`remote_link`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;


DROP TABLE IF EXISTS `tools_info`;
CREATE TABLE `tools_info` (
  `tool_name` varchar(255) NOT NULL,
  `info` json NOT NULL,
  UNIQUE KEY `donor_name` (`tool_name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;


DROP TABLE IF EXISTS `tools_config`;
CREATE TABLE `tools_config` (
  `tool_name` varchar(255) NOT NULL,
  `config` json NOT NULL,
  UNIQUE KEY `tool_name` (`tool_name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;


DROP TABLE IF EXISTS `users`;
CREATE TABLE `users` (
  `user_id` int NOT NULL AUTO_INCREMENT,
  `user_name` varchar(25) NOT NULL,
  `user_email` varchar(255) NOT NULL,
  `user_login` varchar(255) NOT NULL,
  `user_pass` varchar(255) NOT NULL,
  `user_status` varchar(10) NOT NULL DEFAULT 'user',
  PRIMARY KEY (`user_id`),
  UNIQUE KEY `login` (`user_login`),
  UNIQUE KEY `user_email` (`user_email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;

INSERT INTO `users` (`user_id`, `user_name`, `user_email`, `user_login`, `user_pass`, `user_status`) VALUES
(1,	'SAdmin',	'test@test.ru',	'sadmin',	'$2y$10$pfwPBd//LV1u8ZTJIpXOzeHh6knECK2g/l4bj6arfWAMxnVM2VzXe',	'superadmin'),
(5,	'Admin',	'admin@test.ru',	'admin',	'$2y$10$7eMAVUvLWGgWZLK/fe.mDuGZ.7NS7TPoUaPNkjVdweXVPUXFmtKT.',	'admin'),
(7,	'Banned U',	'log23@q.ru',	'buser',	'$2y$10$7eMAVUvLWGgWZLK/fe.mDuGZ.7NS7TPoUaPNkjVdweXVPUXFmtKT.',	'banned'),
(9,	'Just User',	'admin@jvca.ru',	'user',	'$2y$10$7eMAVUvLWGgWZLK/fe.mDuGZ.7NS7TPoUaPNkjVdweXVPUXFmtKT.',	'user');

-- 2023-06-14 19:10:34
