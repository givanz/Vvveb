DROP TABLE IF EXISTS `post_content`;

CREATE TABLE `post_content` (
`post_id` INT NOT NULL,
`language_id` INT NOT NULL,
`name` TEXT NOT NULL,
`slug` TEXT NOT NULL,
`content` TEXT NOT NULL,
`excerpt` text DEFAULT "",
`meta_keywords` text NOT NULL DEFAULT "",
`meta_description` text NOT NULL DEFAULT "",
PRIMARY KEY (`post_id`,`language_id`)
);

CREATE INDEX `post_content_slug` ON `post_content` (`slug`);

DROP TRIGGER IF EXISTS `afterPostContentInsert`;
DROP TRIGGER IF EXISTS `afterPostContentDelete`;
DROP TABLE IF EXISTS `post_content_search`;

CREATE VIRTUAL TABLE post_content_search USING fts5(
  content='post_content', 
  content_rowid='rowid', 
  name, 
  content 
);

CREATE TRIGGER afterPostContentInsert AFTER INSERT ON post_content BEGIN
  INSERT INTO post_content_search(
    rowid, 
    name, 
    content
  )
  VALUES(
    new.rowid,
    new.name, 
    new.content
);END;

CREATE TRIGGER afterPostContentDelete AFTER DELETE ON post_content BEGIN
  INSERT INTO post_content_search(
    post_content_search,
    rowid,
    name,
    content
  )
  VALUES(
    'delete',
    old.rowid,
    old.name,
    old.content
);END;
