DROP TABLE IF EXISTS vendor_to_site;

CREATE TABLE vendor_to_site (
  "vendor_id" int check ("vendor_id" > 0) NOT NULL,
  "site_id" smallint NOT NULL,
  PRIMARY KEY ("vendor_id","site_id")
);
