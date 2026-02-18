DROP TABLE IF EXISTS menu_item_meta;

-- DROP SEQUENCE IF EXISTS menu_item_meta_menu_item_meta_id_seq;
-- CREATE SEQUENCE menu_item_meta_menu_item_meta_id_seq;


CREATE TABLE menu_item_meta (
  "menu_item_id" int check ("menu_item_id" > 0) NOT NULL,
  "namespace" varchar(191) NOT NULL DEFAULT '',
  "key" varchar(191) NOT NULL,
  "value" text DEFAULT NULL,
  PRIMARY KEY ("menu_item_id", "namespace", "key")
);

-- CREATE UNIQUE INDEX "menu_item_id" ON menu_item_meta ("menu_item_id", "namespace", "key");
-- CREATE INDEX "menu_item_meta_key" ON menu_item_meta ("key");
-- SELECT setval('menu_item_meta_menu_item_meta_id_seq', 0, true); -- last inserted id by sample data