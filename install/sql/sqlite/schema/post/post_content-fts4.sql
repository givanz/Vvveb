DROP TABLE IF EXISTS `post_content`;

CREATE TABLE `post_content` (
`post_id` INT NOT NULL,
`language_id` INT NOT NULL,
`name` TEXT NOT NULL DEFAULT "",
`slug` TEXT NOT NULL DEFAULT "",
`content` TEXT,
`excerpt` text,
`meta_keywords` text NOT NULL DEFAULT "",
`meta_description` text NOT NULL DEFAULT ""
,PRIMARY KEY (`post_id`,`language_id`)
-- FULLTEXT `search` (`name`,`content`)
);

CREATE INDEX `post_content_slug` ON `post_content` (`slug`);

DROP TABLE IF EXISTS `post_content_search`;

create virtual table post_content_search using fts4(
  content='post_content', 
--  content_rowid='rowid', 
  name, 
  content 
);

create trigger afterPostContentInsert after insert on post_content begin
  insert into post_content_search(
    rowid, 
    name, 
    content
  )
  values(
    new.rowid,
    new.name, 
    new.content
);end;

create trigger afterPostContentDelete after delete on post_content begin
  insert into post_content_search(
    post_content_search,
    rowid,
    name,
    content
  )
  values(
    'delete',
	old.rowid,
    old.name,
    old.content
);end;
