DROP TABLE IF EXISTS `product_content`;

CREATE TABLE `product_content` (
`product_id` INT NOT NULL,
`language_id` INT NOT NULL,
`name` TEXT NOT NULL DEFAULT "",
`slug` TEXT NOT NULL DEFAULT "",
`content` text,
`excerpt` text DEFAULT "",
`meta_title` TEXT NOT NULL DEFAULT "",
`meta_description` TEXT NOT NULL DEFAULT "",
`meta_keywords` TEXT NOT NULL DEFAULT "",
PRIMARY KEY (`product_id`,`language_id`)
);

CREATE INDEX `product_content_slug` ON `product_content` (`slug`);

DROP TRIGGER IF EXISTS `afterProductContentInsert`;
DROP TRIGGER IF EXISTS `afterProductContentDelete`;
DROP TABLE IF EXISTS `product_content_search`;

CREATE VIRTUAL TABLE product_content_search USING fts5(
  content='product_content', 
  content_rowid='rowid', 
  `name`, 
  `content` 
);

CREATE TRIGGER afterProductContentInsert AFTER INSERT ON product_content BEGIN
  INSERT INTO product_content_search(
    rowid, 
    name, 
    content
  )
  VALUES(
    new.rowid,
    new.name, 
    new.content
);END;

CREATE TRIGGER afterProductContentDelete AFTER DELETE ON product_content BEGIN
  INSERT INTO product_content_search(
    product_content_search,
    rowid,
    `name`,
    `content`
  )
  VALUES(
    'delete',
    old.rowid,
    old.name,
    old.content
);END;
