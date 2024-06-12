DROP TABLE IF EXISTS manufacturer_to_site;

CREATE TABLE manufacturer_to_site (
  "manufacturer_id" int check ("manufacturer_id" > 0) NOT NULL,
  "site_id" smallint NOT NULL,
  PRIMARY KEY ("manufacturer_id","site_id")
);
