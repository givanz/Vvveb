DROP TABLE IF EXISTS post_to_menu;

CREATE TABLE post_to_menu (
  "post_id" int check ("post_id" > 0) NOT NULL,
  "menu_id" int check ("menu_id" > 0) NOT NULL,
  PRIMARY KEY ("post_id","menu_id")
);

CREATE INDEX "menu_id" ON post_to_menu ("menu_id");
