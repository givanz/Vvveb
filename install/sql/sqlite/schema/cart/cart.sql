DROP TABLE IF EXISTS `cart`;

CREATE TABLE `cart` (
  `cart_id` INTEGER PRIMARY KEY AUTOINCREMENT,
  `user_id` INT NOT NULL DEFAULT 0,
  `session_id` varchar(32) NOT NULL DEFAULT '',
  `data` TEXT NOT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP
--  PRIMARY KEY (`cart_id`),
--  KEY `user_id` (`user_id`)
);

CREATE INDEX `cart_user_id` ON `cart` (`user_id`);
