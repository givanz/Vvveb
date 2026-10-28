DROP TABLE IF EXISTS `media`;

CREATE TABLE `media` (
`media_id` INTEGER PRIMARY KEY AUTOINCREMENT,
`file` TEXT NOT NULL,
`type` TEXT NOT NULL default 'image/png',
`meta` TEXT
-- PRIMARY KEY (`media_id`)
);


CREATE INDEX `media_image` ON `media` (`file`,`media_id`);

