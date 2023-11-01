DROP TABLE IF EXISTS menu_type_content;
CREATE TABLE menu_type_content (
  "menu_type_id" int check ("menu_type_id" > 0) NOT NULL,
  "language_id" int check ("language_id" > 0) NOT NULL,
  "name" varchar(191) NOT NULL,
  "slug" varchar(191) NOT NULL DEFAULT '',
  "content" text NOT NULL,
  PRIMARY KEY ("menu_type_id","language_id")
);

CREATE INDEX "menu_type_content_name" ON menu_type_content ("name");