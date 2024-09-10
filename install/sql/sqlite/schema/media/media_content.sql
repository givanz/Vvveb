DROP TABLE IF EXISTS `media_content`;

CREATE TABLE `media_content` (
`media_id` INT NOT NULL,
`language_id` INT NOT NULL,
`name` TEXT NOT NULL DEFAULT "",
`caption` TEXT NOT NULL DEFAULT "",
`description` TEXT NOT NULL DEFAULT "",
--`slug` TEXT NOT NULL DEFAULT "",
--`content` TEXT,
--`excerpt` text,
PRIMARY KEY (`media_id`,`language_id`)
);

CREATE INDEX `media_content_name` ON `media_content` (`name`);
-- CREATE INDEX `media_content_alt` ON `media_content` (`alt`);
