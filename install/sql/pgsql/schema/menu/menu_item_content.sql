DROP TABLE IF EXISTS menu_item_content;
CREATE TABLE menu_item_content (
  "menu_item_id" int check ("menu_item_id" > 0) NOT NULL,
  "language_id" int check ("language_id" > 0) NOT NULL,
  "name" varchar(191) NOT NULL,
  "slug" varchar(191) NOT NULL DEFAULT '',
  "content" text NOT NULL,
  PRIMARY KEY ("menu_item_id","language_id")
);

CREATE INDEX "menu_item_content_name" ON menu_item_content ("name");