DROP TABLE IF EXISTS `product_content`;

CREATE TABLE `product_content` (
`product_id` INT NOT NULL,
`language_id` INT NOT NULL,
`name` TEXT NOT NULL DEFAULT "",
`slug` TEXT NOT NULL DEFAULT "",
`content` text,
`tag` text,
`meta_title` TEXT NOT NULL DEFAULT "",
`meta_description` TEXT NOT NULL DEFAULT "",
`meta_keywords` TEXT NOT NULL DEFAULT "",
PRIMARY KEY (`product_id`,`language_id`)
-- FULLTEXT `search` (`name`,`content`)
);


CREATE INDEX `product_content_slug` ON `product_content` (`slug`);

DROP TABLE IF EXISTS `product_content_search`;

create virtual table product_content_search using fts4(
  content='product_content', 
--  content_rowid='rowid', 
  name, 
  content 
);

create trigger afterProductContentInsert after insert on product_content begin
  insert into product_content_search(
    rowid, 
    name, 
    content
  )
  values(
    new.rowid,
    new.name, 
    new.content
);end;

create trigger afterProductContentDelete after delete on product_content begin
  insert into product_content_search(
    product_content_search,
    rowid,
    name,
    content
  )
  values(
    'delete',
    old.name,
    old.content
);end;
