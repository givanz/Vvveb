DROP TABLE IF EXISTS menu_to_site;
CREATE TABLE menu_to_site (
  "menu_id" int check ("menu_id" > 0) NOT NULL,
  "site_id" smallint NOT NULL,
  PRIMARY KEY ("menu_id","site_id")
);