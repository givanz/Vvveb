DROP TABLE IF EXISTS menu_item_meta;

DROP SEQUENCE IF EXISTS menu_item_meta_seq;
CREATE SEQUENCE menu_item_meta_seq;


CREATE TABLE menu_item_meta (
  "menu_item_meta_id" int check ("menu_item_meta_id" > 0) NOT NULL DEFAULT NEXTVAL ('menu_item_meta_seq'),
  "menu_item_id" int check ("menu_item_id" > 0) NOT NULL DEFAULT 0,
  "key" varchar(191) DEFAULT NULL,
  "value" text DEFAULT NULL,
  PRIMARY KEY ("menu_item_meta_id")
);

CREATE INDEX "menu_item_id" ON menu_item_meta ("menu_item_id");
CREATE INDEX "menu_item_meta_key" ON menu_item_meta ("key");