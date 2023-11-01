DROP TABLE IF EXISTS `weight_type`;

CREATE TABLE `weight_type` (
`weight_type_id` INTEGER PRIMARY KEY AUTOINCREMENT,
`value` decimal(15,8) NOT NULL DEFAULT '0.00000000'
-- PRIMARY KEY (`weight_type_id`)
);
