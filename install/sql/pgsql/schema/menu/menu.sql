DROP TABLE IF EXISTS menu;

DROP SEQUENCE IF EXISTS menu_seq;
CREATE SEQUENCE menu_seq;


CREATE TABLE menu (
  "menu_id" int check ("menu_id" > 0) NOT NULL DEFAULT NEXTVAL ('menu_seq'),
  "name" varchar(191) NOT NULL DEFAULT '',
  "slug" varchar(191) NOT NULL DEFAULT '',
  PRIMARY KEY ("menu_id")
);

CREATE INDEX "menu_menu_id" ON menu ("menu_id");