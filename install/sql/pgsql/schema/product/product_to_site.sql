DROP TABLE IF EXISTS product_to_site;

CREATE TABLE product_to_site (
  "product_id" int check ("product_id" > 0) NOT NULL,
  "site_id" smallint NOT NULL DEFAULT 0,
  PRIMARY KEY ("product_id","site_id")
);
