DROP TABLE IF EXISTS `post_to_taxonomy_item`;

CREATE TABLE `post_to_taxonomy_item` (
`post_id` INT NOT NULL,
`taxonomy_item_id` INT NOT NULL,
PRIMARY KEY (`post_id`,`taxonomy_item_id`)
);

CREATE INDEX `post_to_taxonomy_item_taxonomy_item_id` ON `post_to_taxonomy_item` (`taxonomy_item_id`);
