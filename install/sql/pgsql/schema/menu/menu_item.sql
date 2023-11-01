DROP TABLE IF EXISTS menu_item;

DROP SEQUENCE IF EXISTS menu_item_seq;
CREATE SEQUENCE menu_item_seq;


CREATE TABLE menu_item (
  "menu_item_id" int check ("menu_item_id" > 0) NOT NULL DEFAULT NEXTVAL ('menu_item_seq'),
  "menu_id" int check ("menu_id" > 0) NOT NULL,
  "image" varchar(191) NOT NULL DEFAULT '',
  "url" varchar(191) NOT NULL DEFAULT '',
  "parent_id" int check ("parent_id" >= 0) NOT NULL DEFAULT 0,
  "item_id" int check ("item_id" > 0) DEFAULT NULL,
  "sort_order" int NOT NULL DEFAULT 0,
  "status" smallint NOT NULL DEFAULT 0,
  PRIMARY KEY ("menu_item_id")
);

CREATE INDEX "menu_item_parent_id" ON menu_item ("parent_id");